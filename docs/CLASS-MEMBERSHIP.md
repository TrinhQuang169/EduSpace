# EduSpace — lớp học, mã tham gia và thành viên

Hoàn tất ngày 14/10/2026. Tên bảng `courses`, `enrollments`, các cột cũ và URL được giữ nguyên. Nhãn giao diện đổi sang lớp học, học sinh và giáo viên; nội dung người dùng đã lưu không bị sửa.

## Chạy migration một lần

1. Trong phpMyAdmin, chọn database **online_learning** đang dùng.
2. Chọn **Import → Choose file → database/20261013_class_membership.sql → Go**. Không import lại bản dump dữ liệu đầy đủ.
3. Tải lại **Danh sách lớp học** của học sinh hoặc **Lớp học của tôi** của giáo viên. Hệ thống tự cấp mã cho tất cả lớp cũ chưa có mã bằng `random_bytes()` của PHP. Giáo viên xem mã trong danh sách lớp của mình hoặc trang thành viên.

File SQL có thể chạy lại: kiểm tra cột/index trước khi thêm, dùng `CREATE TABLE IF NOT EXISTS` cho bảng giới hạn thử. Không xóa hoặc sửa dữ liệu hiện có. Các lớp cũ tiếp tục giữ danh sách thành viên và lịch sử học tập.

MariaDB trên máy là 10.4.32 và không có hàm SQL `RANDOM_BYTES()`. Vì vậy SQL thêm cột nullable, sau đó PHP backfill mã an toàn. Giá trị NULL chỉ phục vụ chuyển tiếp; mã NULL không bao giờ cho phép tham gia. Backfill chỉ cập nhật NULL, có kiểm tra đồng thời và thử lại nếu gặp trùng mã. Mã đã cấp không bị đổi khi tải lại trang.

**Migration chưa được chạy trên database thật trong quá trình triển khai.** Trước khi chạy, các trang mới hiển thị hướng dẫn và endpoint tham gia trả 503; không rơi về cơ chế đăng ký không cần mã.

## Hành vi

- Mã có 16 ký tự hexadecimal viết hoa, sinh từ `random_bytes(8)` và có UNIQUE INDEX. Lớp mới nhận mã ngay trong INSERT. Nhập mã không phân biệt chữ hoa/thường; server vẫn kiểm tra đầy đủ định dạng và giá trị bằng `hash_equals()`.
- Mã chỉ hiển thị trên trang giáo viên sau khi kiểm tra sở hữu. Danh sách học sinh và trang chủ không SELECT hoặc nhúng mã vào HTML/JavaScript.
- Học sinh chưa tham gia mở popup nhập mã. Form vẫn hoạt động khi tắt JavaScript bằng liên kết `courses.php?join=...`. Học sinh đã tham gia đi thẳng vào lớp.
- `student/enroll.php` chỉ nhận POST, kiểm tra session, vai trò, CSRF, trạng thái `approved`, mã và giới hạn thử. Không có endpoint tạo enrollment khác ngoài service được endpoint này gọi.
- Sau 5 lần sai trong cửa sổ 15 phút, yêu cầu tiếp theo bị từ chối HTTP 429 với `Retry-After`. Bộ đếm lưu theo user ID trong MySQL, dùng chung cho mọi lớp và phiên đăng nhập. Đăng xuất, đổi cookie hoặc nhập thành công một mã khác không đặt lại số lần sai.
- Giáo viên vào `teacher/members.php`, chọn lớp của mình để xem tên, email, ngày tham gia, số bài hoàn thành, tỷ lệ tiến độ, số quiz và điểm trung bình. Nút xóa dùng POST, CSRF và xác nhận.
- Xóa thành viên chỉ xóa enrollment. Bài làm, điểm số và tiến độ được giữ lại. Học sinh có thể tham gia lại nếu có đúng mã; đây là xóa tư cách thành viên, không phải cấm tham gia vĩnh viễn.
- Mỗi yêu cầu học bài, xem/tải tài liệu, làm/xem quiz và xem/ghi tiến độ đều kiểm tra enrollment hiện tại ở server. Gỡ thành viên và tham gia lớp khóa cùng bản ghi lớp trong transaction để phối hợp với việc nộp quiz. Các trang đăng nhập có `Cache-Control: private, no-store`.

## File chính

| File | Thay đổi |
| --- | --- |
| `database/20261013_class_membership.sql` | Cột mã UNIQUE và bảng giới hạn số lần thử |
| `includes/classes.php` | Sinh/backfill mã, kiểm tra mã, giới hạn thử, transaction tham gia/xóa thành viên |
| `student/enroll.php` | Endpoint bắt buộc mã; phản hồi HTML hoặc JSON; không còn INSERT bỏ qua mã |
| `student/courses.php` | Nút tham gia, form mã và fallback không JavaScript |
| `assets/js/class-join.js` | Modal có quản lý focus, Escape, gửi POST và hiển thị lỗi server |
| `teacher/create_course.php` | Sinh mã khi tạo lớp, xử lý trùng mã |
| `teacher/courses.php` | Chỉ lấy mã lớp sở hữu, liên kết quản lý thành viên |
| `teacher/members.php` | Danh sách học sinh và POST xóa thành viên |
| `includes/ui.php`, `assets/css/app.css` | Điều hướng thành viên, nhãn trạng thái và giao diện mới |
| `includes/auth.php` | Ngăn cache nội dung đã xác thực; giữ nguyên cơ chế session và vai trò |
| `index.php`, các trang trong `admin/`, `teacher/`, `student/`, `dashboards/`, `includes/` | Đổi nhãn giao diện và thông báo, không đổi tên route/bảng |
| `tests/class_membership.php`, `tests/class_fixtures.php` | Kiểm thử CLI bằng bảng TEMPORARY, không ghi dữ liệu thật |

## Kiểm thử

Kết quả: **46 file PHP qua kiểm tra cú pháp; 34 kiểm tra logic/SQL và 41 kiểm tra HTTP/phân quyền đạt**. Chrome headless kiểm tra popup nhập mã và trang thành viên tại **1440, 768, 390 px**: không có exception JavaScript hay tràn ngang toàn trang. Đã thử phản hồi mã sai trong popup, validation, Escape và trả focus; đã xem ảnh chụp thực tế.

Chạy lại kiểm thử logic bằng `php tests/class_membership.php` khi MySQL đang hoạt động. Script đọc schema hiện có để tạo bảng TEMPORARY cùng tên trên kết nối kiểm thử. Mọi INSERT/UPDATE/DELETE diễn ra trong bảng tạm, tự mất khi đóng kết nối. Script không chạy qua HTTP.

Đã kiểm tra mã đúng/sai/thiếu/mảng, mã lớp khác, lớp pending/draft, UNIQUE INDEX, backfill ổn định, hết thời gian khóa, lượt tham gia trùng, giáo viên khác truy cập/xóa, CSRF, gọi trực tiếp URL, giữ lịch sử và chặn truy cập sau khi xóa. SQL thêm cột/index/bộ đếm được thực thi trên bảng tạm; kiểu dữ liệu khóa ngoại được đối chiếu với schema thật. InnoDB không hỗ trợ FK trên bảng tạm, nên FK không được tạo trong fixture.

Các kết quả kiểm thử trên fixture không thay thế việc import migration vào database thật. Không nhập dữ liệu giả vào giao diện chạy thực tế, không xóa/đặt lại database thật và không thay đổi cấu hình kết nối.

## Sao lưu

Trước khi sửa đã sao lưu toàn bộ workspace hiện tại (bao gồm giao diện đã nâng cấp, không chỉ bản HEAD cũ), trừ `.git`, vào file `eduspace-before-classes-395f35ff4a244992a4860ddaf5cdcc7c.zip` trong thư mục TEMP của Windows. Bản SQL dữ liệu gốc và `config/database.php` giữ nguyên. Khi cần hoàn tác code, dùng bản sao lưu này để giữ lại giao diện của lượt nâng cấp trước.
