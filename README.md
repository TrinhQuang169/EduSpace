# EduSpace – Nền tảng quản lý và hỗ trợ học tập trực tuyến

Đồ án xây dựng hệ thống học tập trực tuyến, hỗ trợ ba vai trò: Quản trị viên (Admin), Giảng viên (Teacher) và Học viên (Student).

## 1. Công nghệ sử dụng

- PHP
- MySQL
- HTML, CSS, JavaScript
- XAMPP
- Visual Studio Code

## 2. Yêu cầu cài đặt

Máy tính cần có:

- XAMPP hỗ trợ PHP và MySQL/MariaDB
- Trình duyệt Chrome hoặc Edge
- Git (nếu tải bằng lệnh Git)

## 3. Tải mã nguồn

Mở thư mục `htdocs` của XAMPP và chạy:

```bash
git clone https://github.com/TrinhQuang169/EduSpace.git online-learning
```

Hoặc vào GitHub, chọn **Code → Download ZIP**, giải nén và đổi tên thư mục thành `online-learning`, sau đó chép vào `htdocs`.

## 4. Khởi động XAMPP

1. Mở XAMPP Control Panel.
2. Bật Apache.
3. Bật MySQL.
4. Truy cập `http://localhost/phpmyadmin`.

## 5. Cài đặt cơ sở dữ liệu

1. Mở phpMyAdmin.
2. Chọn **New** để tạo database.
3. Đặt tên database là `online_learning`.
4. Chọn database vừa tạo.
5. Chọn tab **Import**.
6. Chọn file `database/online_learning.sql`.
7. Nhấn **Import** hoặc **Go** để hoàn tất.

## 6. Cấu hình kết nối MySQL

Mở file `config/database.php`.

Thiết lập các thông số kết nối phù hợp với MySQL trên máy.

Thông số thường gặp khi dùng XAMPP mặc định:

- Host: `localhost`
- Database: `online_learning`
- Username: `root`
- Password: để trống nếu MySQL chưa đặt mật khẩu

Đảm bảo file cấu hình khởi tạo biến kết nối PDO có tên `$pdo` để các trang PHP sử dụng.

Nếu dự án có cấu hình đường dẫn lưu tài liệu trong `config/storage.php`, hãy chỉnh lại đường dẫn cho phù hợp với máy của bạn và cấp quyền ghi cần thiết. Thư mục lưu tài liệu nên nằm ngoài thư mục website có thể truy cập công khai.

## 7. Chạy website

Truy cập:

http://localhost/online-learning/

Lưu ý: Nếu Apache sử dụng cổng khác 80, cần thêm số cổng vào URL.

## 8. Các vai trò

**Admin**
- Quản lý tài khoản.
- Duyệt khóa học.
- Theo dõi thống kê hệ thống.

**Teacher**
- Tạo và quản lý khóa học.
- Quản lý bài học và tài liệu.
- Tạo câu hỏi trắc nghiệm.
- Theo dõi tiến độ học viên.

**Student**
- Xem và đăng ký khóa học.
- Học bài, xem tài liệu.
- Làm bài trắc nghiệm.
- Xem điểm và tiến độ học tập.

## 9. Lưu ý

- Không đưa mật khẩu MySQL thật, khóa bí mật hay dữ liệu cá nhân lên GitHub.
- File SQL trong repository chỉ nên chứa cấu trúc và dữ liệu mẫu an toàn để chia sẻ.
- Các tệp tài liệu học tập đã tải lên có thể được lưu riêng bên ngoài thư mục mã nguồn. File SQL không bao gồm các tệp này.
- Cần có tài khoản được cấp quyền Admin/Teacher để sử dụng các tính năng tương ứng.
