# Giao diện EduSpace — 14/10/2026

Nâng cấp các trang nội bộ thông qua bộ component chung, giữ phong cách trang chủ và đăng nhập hiện có.

- `assets/css/visuals.css`: sidebar tím đậm, header trong nhẹ, dashboard gradient, thẻ số liệu, card lớp học, bảng, form, trắc nghiệm và popup đồng bộ. Có responsive, focus bàn phím, chế độ giảm chuyển động và in.
- `assets/images/learning-orbit.svg`: minh họa sách và mũ tốt nghiệp tự vẽ bằng SVG, không phụ thuộc dịch vụ bên ngoài.
- `assets/images/class-pattern.svg`: hình nền dùng chung cho card lớp học.
- `includes/ui.php`: nạp CSS mới và thêm lớp màu cho các thẻ số liệu.
- `includes/dashboard.php`: dùng minh họa mới trên dashboard cả ba vai trò.

Không thay đổi truy vấn SQL, dữ liệu, phân quyền, CSRF, mã lớp, URL hay xử lý form trong lần nâng cấp này. Không cần chạy thêm migration cho phần giao diện.

Kiểm tra Chrome tại 1440, 768 và 390 px cho dashboard ba vai trò, danh sách lớp học sinh, thành viên lớp, tạo lớp và trắc nghiệm: 21 lượt hiển thị không phát hiện tràn ngang toàn trang hoặc lỗi PHP/JavaScript. Kiểm tra mở/đóng popup nhập mã và menu điện thoại. Dữ liệu kiểm thử dùng bảng TEMPORARY riêng trên mỗi kết nối, không thay thế dữ liệu thật. Chặn Google Fonts trong lượt kiểm tra để xác nhận giao diện vẫn dùng được với font dự phòng khi mất kết nối ngoài.

Kiểm tra bổ sung 7 trang ở độ rộng 320 px cũng đạt; tổng cộng 28 lượt hiển thị. Toàn bộ 46 file PHP vượt qua kiểm tra cú pháp. Máy chủ, hồ sơ trình duyệt và file kiểm thử tạm đã được dọn sau khi kiểm tra.

Bản sao lưu trước lần chỉnh sửa này: `%TEMP%/eduspace-before-visuals-e46d5dcb56ed4a199e016a80b7d46fbc.zip`.
