<?php
// Bắt đầu output buffering để tránh lỗi HTML xuất hiện
ob_start();

// Tắt hiển thị lỗi trực tiếp ra trình duyệt
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// Báo cho trình duyệt biết đây là JSON
header('Content-Type: application/json');

// Kết nối database
require 'db.php'; // file này phải tạo sẵn $mysqli

if ($mysqli->connect_errno) {
    echo json_encode(['error' => 'Không kết nối được database']);
    exit;
}

// Kiểm tra tham số
if (!isset($_GET['ma_hd'])) {
    echo json_encode(['error' => 'Chưa có mã hợp đồng']);
    exit;
}

$ma_hd = intval($_GET['ma_hd']);

// Prepare query
$sql = "SELECT khach_hang, sdt_kh, tien_theo_ky FROM hopdong WHERE ma_hd = ?";
$stmt = $mysqli->prepare($sql);

if (!$stmt) {
    echo json_encode(['error' => 'Lỗi prepare: '.$mysqli->error]);
    exit;
}

$stmt->bind_param('i', $ma_hd);
$stmt->execute();
$stmt->bind_result($khach_hang, $sdt_kh, $tien_theo_ky);

if ($stmt->fetch()) {
    // Xóa mọi output dư thừa trước khi echo JSON
    ob_clean();
    echo json_encode([
        'khach_hang' => $khach_hang,
        'sdt_kh' => $sdt_kh,
        'tien_theo_ky' => $tien_theo_ky
    ]);
} else {
    ob_clean();
    echo json_encode(['error' => 'Không tìm thấy hợp đồng']);
}

$stmt->close();
$mysqli->close();
?>
