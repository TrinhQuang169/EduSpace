# EduSpace — minh họa và hiệu ứng chuyển động

Cập nhật ngày 14/10/2026, sau bản nâng cấp giao diện.

- Minh họa SVG gồm sách, mũ tốt nghiệp, nút phát và vòng quỹ đạo chuyển động độc lập; dùng chung ở dashboard ba vai trò và trang chủ.
- Nền ánh sáng nhẹ, hạt sáng, nhãn nổi, logo có vệt sáng; card xuất hiện khi cuộn, có ánh sáng theo chuột và độ nghiêng nhỏ trên máy tính.
- Nút bấm có hiệu ứng gợn, popup mở mềm, icon và vòng điểm số có chuyển động.
- Nút **Hiệu ứng** trên thanh đầu trang bật/tắt và lưu lựa chọn bằng localStorage. Tự tắt theo `prefers-reduced-motion`; dừng khi tab bị ẩn. Chuyển động của minh họa ngoài màn hình được tạm dừng. Không ẩn nội dung khi JavaScript chưa tải hoặc bị tắt.

## File thay đổi

- `assets/css/effects.css`: lớp trang trí, responsive, chuyển động và chế độ giảm chuyển động.
- `assets/js/effects.js`: tùy chọn chuyển động, observer, ánh sáng theo chuột và gợn nút. Không gọi `preventDefault`, không thay đổi dữ liệu hoặc sự kiện gửi form.
- `assets/images/learning-scene.svg`: minh họa vector cục bộ, không cần thư viện hay CDN.
- `includes/artwork.php`: component minh họa và nút bật/tắt.
- `includes/ui.php`: nạp CSS/JS chung và thêm nút trên header.
- `includes/dashboard.php`: dùng minh họa mới.
- `index.php`: áp dụng minh họa và hiệu ứng trên trang chủ, giữ truy vấn và chức năng tìm kiếm cũ.

## Kiểm tra

- 4 file PHP mới/sửa đều vượt qua kiểm tra cú pháp.
- Chrome: 32 lượt kiểm tra bố cục (8 trang × 1440, 768, 390, 320 px), không phát hiện lỗi PHP/JavaScript hoặc tràn ngang toàn trang.
- Popup nhập mã mở/đóng ở cả 4 kích thước.
- Kiểm tra SVG thực sự chuyển động, tắt mọi animation, lưu tùy chọn qua chuyển trang, đáp ứng thay đổi chế độ giảm chuyển động của hệ điều hành, ánh sáng theo con trỏ, tạo/dọn gợn nút, từ chối mã lớp sai, tìm kiếm trang chủ và khả năng đọc nội dung khi tắt JavaScript.
- Thử với dữ liệu trong bảng TEMPORARY, không ghi vào dữ liệu thật. Chặn Google Fonts trong trình duyệt kiểm thử để kiểm tra font dự phòng.

Không thay đổi database, phân quyền, CSRF, URL hoặc logic nghiệp vụ. Không cần migration mới hay bước build.

Bản sao lưu trước lần sửa: `%TEMP%/eduspace-before-motion-df544d65bc3d44f4b0c031d1270def2f.zip`.
