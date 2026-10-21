# EduSpace — giao diện hệ thống

Cập nhật ngày 13/10/2026. Giao diện được áp dụng trực tiếp cho 22 trang nội bộ, sử dụng PHP, CSS và JavaScript thuần; không cần npm hay bước build.

## Thành phần dùng chung

- `includes/ui.php`: sidebar theo vai trò, header người dùng, footer, SVG icon, thẻ số liệu và nhãn trạng thái. Các trang gọi `eduStart()` sau khi hoàn tất xử lý và kiểm tra quyền, rồi gọi `eduEnd()` ở cuối.
- `includes/dashboard.php`: giao diện tổng quan của ba vai trò; các truy vấn SELECT bổ sung lấy số liệu thật từ schema hiện có. Dữ liệu cá nhân được giới hạn theo tài khoản đăng nhập.
- `assets/css/app.css`: hệ thống giao diện nội bộ, phạm vi `.edu-app`; kế thừa `style.css`. Có bố cục tablet/mobile, cuộn riêng cho bảng, focus bàn phím, chế độ giảm chuyển động và in.
- `assets/js/app.js`: mở/đóng sidebar mobile, Escape, quản lý focus và ngăn tương tác với phần nền khi menu mở. Không can thiệp gửi form. Khi tắt JavaScript, điều hướng vẫn hiển thị.

`index.php`, `auth/`, `assets/css/style.css`, `assets/js/main.js` được giữ nguyên.

## Các trang đã cập nhật

| Nhóm | File |
| --- | --- |
| Dashboard | `dashboards/admin.php`, `dashboards/teacher.php`, `dashboards/student.php` |
| Admin | `admin/courses.php`, `admin/course_preview.php`, `admin/users.php`, `admin/stats.php` |
| Giảng viên | `teacher/courses.php`, `teacher/create_course.php`, `teacher/edit_course.php`, `teacher/lessons.php`, `teacher/create_lesson.php`, `teacher/edit_lesson.php`, `teacher/materials.php`, `teacher/quiz.php`, `teacher/progress.php` |
| Học viên | `student/courses.php`, `student/my_courses.php`, `student/course.php`, `student/lesson.php`, `student/quiz.php`, `student/progress.php` |

Dashboard có thẻ thống kê, truy cập nhanh và khóa học gần đây. Danh sách khóa học học viên dùng card với giảng viên, trạng thái và thao tác. Bảng quản lý, form biên soạn, tải tài liệu và trạng thái được chuẩn hóa. Trang trắc nghiệm sử dụng fieldset, đáp án A/B/C/D, màu kèm chữ cho kết quả đúng/sai và phần giải thích. Tiến độ hiển thị bằng số liệu và thanh progress.

Nút làm trắc nghiệm ở `student/lesson.php` được đưa ra ngoài vòng lặp tài liệu, để xuất hiện một lần khi bài học có trắc nghiệm. Trang xem trước khóa học của Admin có nút duyệt/từ chối khi trạng thái là `pending`, gửi về chính endpoint `review_course.php` với trường và CSRF hiện có.

## Phạm vi giữ nguyên

- Không sửa cấu hình, schema, dữ liệu MySQL, session đăng nhập hay phân quyền.
- Đối chiếu tự động xác nhận toàn bộ phần PHP xử lý trước HTML trong 22 trang giống bản Git gốc, sau khi chuẩn hóa xuống dòng.
- Không sửa các endpoint tạo đăng ký, gửi duyệt, duyệt/từ chối, khóa tài khoản, xóa hay truyền file.
- Form giữ các trường POST/GET, phương thức, action và kiểm tra CSRF đang có. Các helper giao diện dùng tên riêng, không ghi đè biến của trang.
- Các URL xem/tải Word, PDF, MP4 vẫn đi qua endpoint phân quyền cũ. Không bọc HTML vào luồng trả file.

## Kiểm tra đã thực hiện

- `php -l`: toàn bộ PHP ứng dụng, bao gồm các file mới, không có lỗi cú pháp.
- 75 lượt GET với tài khoản, khóa học và bài học thực trong MySQL: trả HTTP 200, không có PHP warning/fatal, thiếu CSRF ở form POST, ID HTML trùng hoặc đường dẫn nội bộ tới file không tồn tại.
- Chrome headless: 69 lượt kiểm tra bố cục tại 1440, 768 và 390 px. Không phát hiện tràn ngang toàn trang, bảng thiếu vùng cuộn, trường form thiếu nhãn hoặc exception JavaScript.
- Kiểm tra menu mobile, nút Escape và trả focus; xem ảnh chụp dashboard, danh sách khóa học, form và trắc nghiệm.
- Cơ sở dữ liệu hiện có chỉ cung cấp một trạng thái kết quả trắc nghiệm phù hợp để kiểm tra. Trạng thái trước khi nộp được kiểm tra bằng bản render tạm dùng câu hỏi thật, không tạo bản ghi: lựa chọn A/B/C/D, validation bắt buộc và không xuất đáp án đúng trước khi nộp đều đạt.
- Server kiểm tra chỉ cho GET, kết nối MySQL đặt ở chế độ transaction read-only, session kiểm tra tách riêng. Không thực hiện nộp bài, duyệt, khóa, tải lên hay xóa dữ liệu thật.

Giới hạn: chưa chạy kiểm thử đầu-cuối cho các thao tác ghi dữ liệu và truyền file nhị phân. Các khối xử lý đó được giữ nguyên; kiểm tra giao diện không thay thế kiểm thử nghiệp vụ đầy đủ. Không phát hiện lỗi giao diện còn tồn tại trong các kích thước đã kiểm tra.

## Sao lưu và hoàn tác

Trạng thái ban đầu sạch tại commit `d9a0d4c`. Trước khi sửa đã tạo `eduspace-before-ui-20261013.zip` trong thư mục TEMP của Windows. Có thể xem thay đổi bằng Git và khôi phục riêng các file giao diện từ bản sao lưu/commit này; các file dùng chung mới được liệt kê ở trên. Không cần phục hồi database vì đợt thay đổi này không ghi dữ liệu.

Các công cụ kiểm tra, session tạm, token, hồ sơ Chrome và server kiểm tra đã được dọn sau khi hoàn tất.
