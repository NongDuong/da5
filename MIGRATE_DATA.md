# Hướng dẫn Import dữ liệu cũ và Nâng cấp Database

Dựa trên ví dụ bạn cung cấp, đây là cách nhanh nhất và an toàn nhất để bạn không bị lỗi khi import:

### Cách xử lý ví dụ của bạn:
Câu lệnh `INSERT` của bạn có liệt kê tên các cột rõ ràng: `INSERT INTO hopdong (ma_hd, ngay_vay, ...)`. Điều này rất tốt!

**Để không bị lỗi, bạn chỉ cần sửa phần `CREATE TABLE` trong file SQL của bạn như sau:**

```sql
CREATE TABLE `hopdong` (
  `ma_hd` int(11) NOT NULL,
  `ngay_vay` date NOT NULL,
  -- ... (các trường khác) ...
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  
  -- THÊM DÒNG NÀY VÀO CUỐI:
  `is_deleted` TINYINT DEFAULT 0 
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
```

**Tại sao cách này không bị lỗi?**
Vì câu lệnh `INSERT` của bạn không nhắc đến cột `is_deleted`, nên khi chạy, MySQL sẽ tự động điền giá trị mặc định là `0` cho cột này. Bạn không cần phải sửa hàng ngàn dòng `INSERT`.

---

### Phương pháp 3: Sử dụng Script tự động (Khuyên dùng)
Nếu bạn lỡ import và bị lỗi "Unknown column is_deleted", bạn chỉ cần chạy file sau (tôi đã tạo sẵn cho bạn):

1. **Import** file SQL cũ của bạn kệ nó báo lỗi code (vì thiếu cột).
2. Mở trình duyệt chạy: `http://localhost/da4/fix_db.php`
3. Hệ thống sẽ tự động thêm cột còn thiếu cho bạn.

---
**Nội dung file fix_db.php:**
Tôi đã tạo sẵn file này trong thư mục của bạn. Bạn chỉ cần chạy nó sau khi import dữ liệu cũ.
