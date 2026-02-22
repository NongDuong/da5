# Hướng dẫn sử dụng Phần mềm Quản lý Hợp đồng Vay (Refactored)

Chào mừng bạn đến với phiên bản cải tiến của phần mềm Quản lý Hợp đồng Vay. Tài liệu này sẽ hướng dẫn bạn cách sử dụng các tính năng mới và quy trình vận hành hệ thống.

---

## 1. Giao diện Chính & Tìm kiếm
- **Tìm kiếm toàn cục**: Nhập tên, SĐT hoặc ghi chú vào ô "Tìm kiếm" ở góc trên bên trái để tìm nhanh mọi hồ sơ.
- **Lọc theo cột**: Dưới tiêu đề các cột (Khách hàng, SDT, Người giới thiệu) có các ô nhập nhỏ. Bạn có thể gõ vào đây để lọc riêng lẻ từng cột.
- **Lọc Trạng thái**: Sử dụng menu thả xuống "Tất cả trạng thái" để xem riêng các hợp đồng Đang vay, Hoàn tất hoặc Nợ xấu.
- **Nút "Chưa đóng"**: Nhấn vào đây để xem danh sách các hợp đồng chưa thu lãi của tháng hiện tại.

## 2. Quản lý Hợp đồng
- **Thêm mới**: Nhấn nút "Thêm hợp đồng".
    - **Bắt buộc**: Phải nhập "Ngày vay" và "Tên khách hàng".
    - **Tự động**: Các ô số tiền sẽ tự nhảy định dạng dấu chấm (VND). Nếu để trống, hệ thống tự coi là 0.
    - **Đặc biệt**: Hệ thống sẽ tự động tạo đủ lịch sử các tháng từ ngày bạn vay đến hiện tại.
- **Sửa thông tin**: Nhấp đúp (Double-click) vào ô cần sửa.
    - Nhấn **Enter** hoặc nhấp chuột ra ngoài để Lưu.
    - **Lưu ý**: Ô "Tổng ghi" không thể sửa thủ công vì nó được tính tự động từ lãi đã thu.
    - Hợp đồng đã "Hoàn tất" sẽ bị khóa không cho sửa các thông tin khác (để tránh sai lệch dữ liệu lịch sử).
- **Xem bí mật**: Ô "Tổng ghi" mặc định bị ẩn. Nhấp đúp vào ô đó để xem giá trị thực.

## 3. Thùng rác (Một bảng duy nhất)
Hệ thống mới không còn chia làm 2 bảng giúp quản lý dễ dàng hơn:
- **Xóa**: Nhấn nút "Xóa" ở cột Thao tác. Hợp đồng sẽ biến mất khỏi danh sách chính.
- **Xem Thùng rác**: Nhấn nút "Thùng rác". Bạn sẽ thấy danh sách các hợp đồng đã xóa.
- **Khôi phục**: Trong chế độ Thùng rác, nhấn nút "Thu" để đưa hợp đồng quay lại danh sách đang quản lý.

## 4. Quản lý Chi tiết (Thu lãi hàng tháng)
Nhấn nút "Chi tiết" ở cuối mỗi dòng hợp đồng:
- **Thêm tháng**: Nếu thiếu tháng, bạn chọn tháng ở ô lịch và nhấn "Thêm tháng".
- **Đánh dấu Thu lãi**: Nhấp đúp vào cột "Trạng thái" trong bảng chi tiết và chọn "Đã thu". 
    - Khi chọn "Đã thu", tiền ở cột **Tổng ghi** bên ngoài sẽ tự động tăng lên.
- **Lọc tháng**: Bạn có thể chọn Tháng/Năm để xem nhanh lịch sử thu lãi của khách hàng đó.

## 5. Xuất báo cáo Excel
- Bạn có thể chọn khoảng ngày (Từ ngày - Đến ngày) sau đó nhấn "Xuất Excel" để tải file báo cáo về máy. Nếu không chọn, hệ thống sẽ tự động trả về những bản ghi của ngày hôm nay.

## 6. Lưu ý kỹ thuật & Bảo trì
- **Tự động cập nhật**: Mỗi khi bạn load trang, hệ thống sẽ tự tính lại "Số ngày" vay để bạn biết khách đã vay được bao lâu.
- **Thông báo**: Các hành động Thêm/Sửa/Xóa thành công sẽ hiện thông báo nhỏ ở góc màn hình và tự biến mất sau 3 giây.
- **Database**: Dữ liệu hiện được lưu tập trung tại bảng `hopdong`. Đừng xóa các bảng cũ này trực tiếp nếu chưa sao lưu.

---
*Mọi thắc mắc trong quá trình sử dụng, hãy liên hệ bộ phận hỗ trợ kỹ thuật.*
