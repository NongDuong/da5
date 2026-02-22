<?php
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

// Cập nhật số ngày tự động khi load
capNhatSoNgayTatCaHopDong($mysqli);
// Tự động tạo bản ghi tháng mới nếu cần
taoChiTietThangMoi($mysqli);

$action = $_REQUEST['action'] ?? '';

// --- HELPER FUNCTIONS ---

function capNhatSoNgayTatCaHopDong($mysqli) {
    date_default_timezone_set('Asia/Ho_Chi_Minh');
    $today = new DateTime();
    $sql = "SELECT ma_hd, ngay_vay FROM hopdong WHERE ngay_vay IS NOT NULL AND ngay_vay <> '' AND ngay_vay <> '0000-00-00'";
    $res = $mysqli->query($sql);
    if (!$res) return 0;

    $stmt = $mysqli->prepare("UPDATE hopdong SET so_ngay = ? WHERE ma_hd = ?");
    $updated = 0;
    while ($row = $res->fetch_assoc()) {
        $ma_hd = (int)$row['ma_hd'];
        $ngay_vay = trim($row['ngay_vay']);
        try {
            $ngayVayDate = new DateTime($ngay_vay);
            $interval = $ngayVayDate->diff($today);
            $so_ngay = (int)$interval->days + 1;
            $stmt->bind_param('ii', $so_ngay, $ma_hd);
            $stmt->execute();
            $updated++;
        } catch (Exception $e) {}
    }
    $stmt->close();
    return $updated;
}

function taoChiTietThangMoi($mysqli) {
    $thangHienTai = date('Y-m');
    $sqlCheck = "SELECT 1 FROM chitiet WHERE DATE_FORMAT(thang, '%Y-%m') = ? LIMIT 1";
    $stmtCheck = $mysqli->prepare($sqlCheck);
    $stmtCheck->bind_param("s", $thangHienTai);
    $stmtCheck->execute();
    if ($stmtCheck->get_result()->num_rows > 0) return;

    $sqlHopDong = "SELECT DISTINCT ma_hd, ngay_vay FROM hopdong WHERE is_deleted = 0";
    $resHD = $mysqli->query($sqlHopDong);
    if (!$resHD) return;

    while ($row = $resHD->fetch_assoc()) {
        $ma_hd = intval($row['ma_hd']);
        $day = $row['ngay_vay'] ? date('d', strtotime($row['ngay_vay'])) : '01';
        $thangMoi = $thangHienTai . '-' . $day;

        $stmt = $mysqli->prepare("INSERT INTO chitiet (ma_hd, thang, trang_thai) VALUES (?, ?, 'chua_thu')");
        $stmt->bind_param("is", $ma_hd, $thangMoi);
        $stmt->execute();
    }
}

function tinh_so_ngay($ngay_vay) {
    if (!$ngay_vay) return 0;
    $today = new DateTime();
    $ngayVay = DateTime::createFromFormat('Y-m-d', substr($ngay_vay, 0, 10));
    if (!$ngayVay) return 0;
    return (int)$today->diff($ngayVay)->days + 1;
}

// --- ACTIONS ---

if ($action === 'list') {
    $is_trash = isset($_GET['trash']) && $_GET['trash'] === '1' ? 1 : 0;
    $q = $_GET['q'] ?? '';
    $chua_dong = $_GET['chua_dong'] ?? '0';
    $sort_by = $_GET['sort_by'] ?? 'ma_hd';
    $sort_dir = $_GET['sort_dir'] ?? 'DESC';
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';

    $allowed_sort = ['ma_hd','ngay_vay','so_ngay','khach_hang','tien_vay','trang_thai'];
    if (!in_array($sort_by, $allowed_sort)) $sort_by = 'ma_hd';
    if (!in_array(strtoupper($sort_dir), ['ASC','DESC'])) $sort_dir = 'DESC';

    $sql = "SELECT *, DATE_FORMAT(ngay_vay, '%Y-%m-%d') as ngay_vay, DATE_FORMAT(gia_han, '%Y-%m-%d') as gia_han 
            FROM hopdong WHERE is_deleted = $is_trash ";

    $params = [];
    $types = '';

    if ($q) {
        $sql .= " AND (khach_hang LIKE ? OR sdt_kh LIKE ? OR ghi_chu LIKE ? OR nguoi_gioi_thieu LIKE ?) ";
        $lk = "%$q%";
        array_push($params, $lk, $lk, $lk, $lk);
        $types .= 'ssss';
    }

    if ($start_date && $end_date) {
        $sql .= " AND ngay_vay BETWEEN ? AND ? ";
        $params[] = $start_date;
        $params[] = $end_date;
        $types .= 'ss';
    } elseif ($start_date) {
        $sql .= " AND ngay_vay >= ? ";
        $params[] = $start_date;
        $types .= 's';
    } elseif ($end_date) {
        $sql .= " AND ngay_vay <= ? ";
        $params[] = $end_date;
        $types .= 's';
    }

    if ($chua_dong === '1') {
        $sql .= " AND EXISTS (SELECT 1 FROM chitiet c WHERE c.ma_hd = hopdong.ma_hd 
                  AND DATE_FORMAT(c.thang, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') 
                  AND c.trang_thai = 'chua_thu') ";
    }

    $sql .= " ORDER BY $sort_by $sort_dir";

    $stmt = $mysqli->prepare($sql);
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    echo json_encode(['data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
    exit;
}

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['ngay_vay','khach_hang','sdt_kh','tien_vay','tien_theo_ky','dong_lai','nguoi_gioi_thieu','sdt_gt','trang_thai'];
    $vals = [];
    foreach($fields as $f) $vals[$f] = $_POST[$f] ?? null;
    $vals['so_ngay'] = tinh_so_ngay($vals['ngay_vay']);

    // Generate a unique random ID for ma_hd
    do {
        $randomId = random_int(10000000, 99999999); // 8-digit random ID
        $checkSql = "SELECT COUNT(*) FROM hopdong WHERE ma_hd = ?";
        $checkStmt = $mysqli->prepare($checkSql);
        $checkStmt->bind_param('i', $randomId);
        $checkStmt->execute();
        $checkStmt->bind_result($count);
        $checkStmt->fetch();
        $checkStmt->close();
    } while ($count > 0);

    $vals['ma_hd'] = $randomId;

    $sql = "INSERT INTO hopdong (ma_hd, ngay_vay, so_ngay, khach_hang, sdt_kh, tien_vay, tien_theo_ky, dong_lai, nguoi_gioi_thieu, sdt_gt, trang_thai) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('isissddssss', $vals['ma_hd'], $vals['ngay_vay'], $vals['so_ngay'], $vals['khach_hang'], $vals['sdt_kh'], 
                                   $vals['tien_vay'], $vals['tien_theo_ky'], $vals['dong_lai'], 
                                   $vals['nguoi_gioi_thieu'], $vals['sdt_gt'], $vals['trang_thai']);
    if ($stmt->execute()) {
        $new_id = $stmt->insert_id;
        
        // Tạo các bản ghi chi tiết từ ngày vay đến tháng hiện tại
        if ($vals['ngay_vay']) {
            $startDate = new DateTime($vals['ngay_vay']);
            $originalDay = (int)$startDate->format('d');
            
            $today = new DateTime();
            $endMonth = new DateTime($today->format('Y-m-t')); // Ngày cuối tháng hiện tại
            
            $current = clone $startDate;
            $stmt2 = $mysqli->prepare("INSERT INTO chitiet (ma_hd, thang, trang_thai) VALUES (?, ?, 'chua_thu')");
            
            while ($current <= $endMonth) {
                // Đảm bảo không vượt quá ngày cuối cùng của tháng đó
                $maxDay = (int)$current->format('t');
                $day = min($originalDay, $maxDay);
                $current->setDate((int)$current->format('Y'), (int)$current->format('m'), $day);
                
                $thangInsert = $current->format('Y-m-d');
                $stmt2->bind_param('is', $new_id, $thangInsert);
                $stmt2->execute();
                
                $current->modify('+1 month');
                $current->setDate((int)$current->format('Y'), (int)$current->format('m'), 1);
            }
            $stmt2->close();
        } else {
            $thang = date('Y-m-d');
            $mysqli->query("INSERT INTO chitiet (ma_hd, thang, trang_thai) VALUES ($new_id, '$thang', 'chua_thu')");
        }
        
        echo json_encode(['success' => true, 'ma_hd' => $new_id]);
    } else {
        echo json_encode(['error' => $stmt->error]);
    }
    exit;
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ma = intval($_POST['ma_hd'] ?? 0);
    $field = $_POST['field'] ?? '';
    $value = $_POST['value'] ?? '';
    
    $allowed = ['ngay_vay','khach_hang','sdt_kh','tien_vay','tien_theo_ky','dong_lai','gia_han','tat_toan','ghi_chu','trang_thai'];
    if (!$ma || !in_array($field, $allowed)) { echo json_encode(['error' => 'Invalid']); exit; }

    if ($field === 'tat_toan') {
        // Special logic for settlement
        $so_tat_toan = floatval($value);
        $mysqli->begin_transaction();
        $res = $mysqli->query("SELECT tien_vay FROM hopdong WHERE ma_hd = $ma FOR UPDATE");
        $row = $res->fetch_assoc();
        if ($so_tat_toan == $row['tien_vay']) {
            $mysqli->query("UPDATE hopdong SET tat_toan = $so_tat_toan, tong_ghi = $so_tat_toan, trang_thai = 'da_hoan_tat' WHERE ma_hd = $ma");
        } else {
            // Partial settlement logic
            $con_lai = $row['tien_vay'] - $so_tat_toan;
            $mysqli->query("UPDATE hopdong SET tien_vay = $con_lai, tong_ghi = 0 WHERE ma_hd = $ma");
            // Insert history record (simplified)
            $mysqli->query("INSERT INTO hopdong (khach_hang, tien_vay, tat_toan, trang_thai, is_deleted) 
                            SELECT khach_hang, $so_tat_toan, $so_tat_toan, 'da_hoan_tat', 0 FROM hopdong WHERE ma_hd = $ma");
        }
        $mysqli->commit();
        echo json_encode(['success' => true]);
        exit;
    }

    $stmt = $mysqli->prepare("UPDATE hopdong SET $field = ? WHERE ma_hd = ?");
    $stmt->bind_param('si', $value, $ma);
    if ($field === 'ngay_vay') {
        $sn = tinh_so_ngay($value);
        $mysqli->query("UPDATE hopdong SET so_ngay = $sn WHERE ma_hd = $ma");
    }
    echo json_encode(['success' => $stmt->execute()]);
    exit;
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ma = intval($_POST['ma_hd'] ?? 0);
    $stmt = $mysqli->prepare("UPDATE hopdong SET is_deleted = 1 WHERE ma_hd = ?");
    $stmt->bind_param('i', $ma);
    echo json_encode(['success' => $stmt->execute()]);
    exit;
}

if ($action === 'restore' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ma = intval($_POST['ma_hd'] ?? 0);
    $stmt = $mysqli->prepare("UPDATE hopdong SET is_deleted = 0 WHERE ma_hd = ?");
    $stmt->bind_param('i', $ma);
    echo json_encode(['success' => $stmt->execute()]);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
