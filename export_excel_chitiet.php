<?php
require_once 'db.php';

$status = $_GET['trang_thai'] ?? '';
$start  = $_GET['start_date'] ?? '';
$end    = $_GET['end_date'] ?? '';

// Điều kiện ngày hôm nay nếu không nhập bất kỳ ngày nào
$todayDay = date('d'); // chỉ lấy ngày (1-31)

$query = "SELECT * FROM hopdong WHERE 1=1";

// Lọc trạng thái
if ($status) {
    $query .= " AND trang_thai='$status'";
}

// Nếu không nhập ngày → lọc theo ngày hôm nay (bất kể tháng/năm)
if (!$start && !$end) {
    $query .= " AND DAY(ngay_vay) = $todayDay";
} else {
    // Nếu có nhập ngày → lọc theo khoảng ngày như bình thường
    if ($start) $query .= " AND DATE(ngay_vay) >= '$start'";
    if ($end)   $query .= " AND DATE(ngay_vay) <= '$end'";
}

$hopdong = $mysqli->query($query);
if (!$hopdong) {
    die("Lỗi query: " . $mysqli->error);
}

// Xuất Excel
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=hopdong.xls");
echo "\xEF\xBB\xBF"; // BOM UTF-8

echo "<table border='1'>";

// Header bảng
echo "<tr>
    <th>Mã HD</th>
    <th>Ngày vay</th>
    <th>Số ngày</th>
    <th>Khách hàng</th>
    <th>SĐT KH</th>
    <th>Tiền vay</th>
    <th>Tiền theo kỳ</th>
    <th>Tổng ghi</th>
    <th>Đóng lãi</th>
    <th>Gia hạn</th>
    <th>Tất toán</th>
    <th>Người giới thiệu</th>
    <th>SĐT GT</th>
    <th>Trạng thái</th>
</tr>";

while ($hd = $hopdong->fetch_assoc()) {
    echo "<tr>
        <td>{$hd['ma_hd']}</td>
        <td>{$hd['ngay_vay']}</td>
        <td>{$hd['so_ngay']}</td>
        <td>{$hd['khach_hang']}</td>
        <td>{$hd['sdt_kh']}</td>
        <td>{$hd['tien_vay']}</td>
        <td>{$hd['tien_theo_ky']}</td>
        <td>{$hd['tong_ghi']}</td>
        <td>{$hd['dong_lai']}</td>
        <td>{$hd['gia_han']}</td>
        <td>{$hd['tat_toan']}</td>
        <td>{$hd['nguoi_gioi_thieu']}</td>
        <td>{$hd['sdt_gt']}</td>
        <td>{$hd['trang_thai']}</td>
    </tr>";
}

echo "</table>";
?>
