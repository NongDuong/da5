<?php
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

$action = $_REQUEST['action'] ?? '';

if ($action === 'list') {
    $ma_hd = intval($_GET['ma_hd'] ?? 0);
    if (!$ma_hd) { echo json_encode(['error'=>'Missing ma_hd']); exit; }
    $sql = "SELECT mact, ma_hd, DATE_FORMAT(thang, '%Y-%m-%d') AS thang, trang_thai, ghi_chu 
            FROM chitiet WHERE ma_hd = ? ORDER BY thang DESC";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $ma_hd);
    $stmt->execute();
    echo json_encode(['data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ma_hd = intval($_POST['ma_hd'] ?? 0);
    $thang = $_POST['thang'] ?? date('Y-m-d');
    $tr = $_POST['trang_thai'] ?? 'chua_thu';
    $ghi = $_POST['ghi_chu'] ?? null;
    
    $stmt = $mysqli->prepare("INSERT INTO chitiet (ma_hd, thang, trang_thai, ghi_chu) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('isss', $ma_hd, $thang, $tr, $ghi);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'mact' => $stmt->insert_id]);
        capNhatTongGhi($mysqli, $ma_hd);
    } else {
        echo json_encode(['error' => $stmt->error]);
    }
    exit;
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $mact = intval($_POST['mact'] ?? 0);
    $field = $_POST['field'] ?? '';
    $value = $_POST['value'] ?? null;
    if (!$mact || !in_array($field, ['thang','trang_thai','ghi_chu'])) { echo json_encode(['error'=>'Invalid']); exit; }

    $stmt = $mysqli->prepare("UPDATE chitiet SET $field = ? WHERE mact = ?");
    $stmt->bind_param('si', $value, $mact);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
        // Update tong_ghi if status changed
        if ($field === 'trang_thai') {
            $res = $mysqli->query("SELECT ma_hd FROM chitiet WHERE mact = $mact");
            $row = $res->fetch_assoc();
            capNhatTongGhi($mysqli, $row['ma_hd']);
        }
    } else {
        echo json_encode(['error' => $stmt->error]);
    }
    exit;
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $mact = intval($_POST['mact'] ?? 0);
    $res = $mysqli->query("SELECT ma_hd FROM chitiet WHERE mact = $mact");
    $row = $res->fetch_assoc();
    $mysqli->query("DELETE FROM chitiet WHERE mact = $mact");
    if ($row) capNhatTongGhi($mysqli, $row['ma_hd']);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'summary') {
    $result = $mysqli->query("SELECT SUM(tien_vay) AS totalLoanAmount, SUM(dong_lai) AS totalInterest FROM hopdong WHERE is_deleted = 0");
    $data = $result->fetch_assoc();
    echo json_encode([
        'totalLoanAmount' => $data['totalLoanAmount'] ?? 0,
        'totalInterest' => $data['totalInterest'] ?? 0
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function capNhatTongGhi($mysqli, $ma_hd) {
    $res = $mysqli->query("SELECT dong_lai FROM hopdong WHERE ma_hd = $ma_hd");
    $hd = $res->fetch_assoc();
    if (!$hd) return;
    
    $res2 = $mysqli->query("SELECT COUNT(*) as count FROM chitiet WHERE ma_hd = $ma_hd AND trang_thai = 'da_thu'");
    $ct = $res2->fetch_assoc();
    
    $tong_ghi = $hd['dong_lai'] * $ct['count'];
    $mysqli->query("UPDATE hopdong SET tong_ghi = $tong_ghi WHERE ma_hd = $ma_hd");
}

echo json_encode(['error' => 'Invalid action']);
