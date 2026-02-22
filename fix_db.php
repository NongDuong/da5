<?php
// fix_db.php
require 'db.php';

echo "<h2>Đang kiểm tra và nâng cấp Database...</h2>";

// 1. Kiểm tra và thêm cột is_deleted vào bảng hopdong
$checkColumn = $mysqli->query("SHOW COLUMNS FROM `hopdong` LIKE 'is_deleted'");
if ($checkColumn->num_rows == 0) {
    if ($mysqli->query("ALTER TABLE `hopdong` ADD COLUMN `is_deleted` TINYINT DEFAULT 0")) {
        echo "<p style='color:green'>[Thành công] Đã thêm cột 'is_deleted' vào bảng hopdong.</p>";
    } else {
        echo "<p style='color:red'>[Lỗi] Không thể thêm cột: " . $mysqli->error . "</p>";
    }
} else {
    echo "<p style='color:blue'>[Thông báo] Cột 'is_deleted' đã tồn tại.</p>";
}

// 2. Tinh chỉnh các trường khác nếu cần (ví dụ cho phép NULL để không bị lỗi khi import)
$mysqli->query("ALTER TABLE `hopdong` MODIFY `tong_ghi` FLOAT DEFAULT 0");
$mysqli->query("ALTER TABLE `hopdong` MODIFY `ghi_chu` TEXT DEFAULT NULL");

echo "<br><p><b>Xong! Bây giờ bạn có thể quay lại trang Quản lý.</b></p>";
echo "<a href='index.php'>Đi tới trang chủ</a>";
?>
