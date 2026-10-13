-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 13, 2026 lúc 04:59 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `online_learning`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(20) DEFAULT '?',
  `color` varchar(20) DEFAULT 'blue',
  `students` int(11) DEFAULT 0,
  `teacher_name` varchar(100) DEFAULT NULL,
  `status` enum('draft','pending','approved','rejected') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `courses`
--

INSERT INTO `courses` (`id`, `teacher_id`, `category`, `title`, `description`, `icon`, `color`, `students`, `teacher_name`, `status`, `created_at`) VALUES
(1, 2, 'Lập trình', 'Lập trình PHP cơ bản-EduSpace', 'Khóa học PHP dành cho người mới bắt đầu.', '💻', 'blue', 120, 'Nguyễn Văn An', 'approved', '2026-10-12 09:53:28'),
(2, 2, 'Thiết kế Web', 'Thiết kế website HTML & CSS', 'Học xây dựng giao diện website.', '🎨', 'purple', 95, 'Trần Thị Mai', 'approved', '2026-10-12 09:53:28'),
(3, 2, 'Lập trình', 'JavaScript từ cơ bản', 'Tìm hiểu JavaScript và lập trình tương tác.', '⚡', 'yellow', 180, 'Lê Minh Tuấn', 'approved', '2026-10-12 09:53:28'),
(4, 2, 'Cơ sở dữ liệu', 'Cơ sở dữ liệu MySQL', 'Học quản lý và truy vấn cơ sở dữ liệu.', '🗄️', 'green', 75, 'Phạm Quốc Huy', 'approved', '2026-10-12 09:53:28'),
(5, 2, 'Lập trình', 'Python cho người mới bắt đầu', 'Làm quen với ngôn ngữ lập trình Python.', '🐍', 'orange', 210, 'Đỗ Thu Hà', 'approved', '2026-10-12 09:53:28'),
(6, 2, 'Trí tuệ nhân tạo', 'Nhập môn trí tuệ nhân tạo', 'Kiến thức cơ bản về AI và học máy.', '🤖', 'pink', 150, 'Hoàng Minh Đức', 'approved', '2026-10-12 09:53:28'),
(7, 2, 'Lập trình', 'Lập trình Web PHP nâng cao 2026', 'Học PHP, MySQL và xây dựng website thực tế', '💻', 'blue', 0, 'Nguyễn Văn Giảng Viên', 'approved', '2026-10-12 15:32:42'),
(9, 2, 'kiểm thử', 'TEST', 'TEST - Khóa học phê duyệt', '📚', 'yellow', 0, 'Nguyễn Văn Giảng Viên', 'approved', '2026-10-12 16:23:01'),
(10, 2, 'ffff', 'nnnnnn', 'ffff', '📚', 'blue', 0, 'Nguyễn Văn Giảng Viên', 'approved', '2026-10-13 11:10:58'),
(11, 2, 'fgdfg', 'fgdfg', 'dfgdfg', '📚', 'green', 0, 'Nguyễn Văn Giảng Viên', 'approved', '2026-10-13 11:20:11'),
(12, 2, 'dfsdf', 'fdsfsdf', 'dfsdfdsf', '⚡', 'orange', 0, 'Nguyễn Văn Giảng Viên', 'approved', '2026-10-13 13:20:01'),
(13, 2, 'Công nghệ thông tin', 'EduSpace Demo - Nhập môn lập trình Web', 'Khóa học giúp học viên tìm hiểu HTML, CSS và JavaScript từ cơ bản. Mỗi bài học có lý thuyết và bài trắc nghiệm.', '📚', 'blue', 0, 'Nguyễn Văn Giảng Viên', 'draft', '2026-10-13 14:32:23');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `enrollments`
--

INSERT INTO `enrollments` (`id`, `student_id`, `course_id`, `enrolled_at`) VALUES
(1, 1, 7, '2026-10-13 11:51:06'),
(2, 1, 6, '2026-10-13 11:51:07'),
(3, 1, 5, '2026-10-13 11:51:07'),
(4, 1, 4, '2026-10-13 11:51:08'),
(5, 1, 3, '2026-10-13 11:51:20'),
(6, 1, 2, '2026-10-13 11:51:20'),
(7, 1, 1, '2026-10-13 11:51:20'),
(8, 1, 11, '2026-10-13 12:19:12'),
(9, 1, 10, '2026-10-13 12:19:13'),
(10, 1, 9, '2026-10-13 12:19:14'),
(11, 1, 12, '2026-10-13 13:24:22');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lessons`
--

CREATE TABLE `lessons` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `lesson_order` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `lessons`
--

INSERT INTO `lessons` (`id`, `course_id`, `title`, `content`, `video_url`, `lesson_order`, `created_at`) VALUES
(1, 9, 'dsjfhajsdhf', 'test linh tinh', 'https://www.youtube.com/watch?v=XqLsUne19Gs&list=RDRb3hfEPyJxQ&index=2', 3, '2026-10-12 16:44:03'),
(3, 9, 'học nói', 'dfasdfdsda', NULL, 3, '2026-10-13 11:10:28'),
(4, 10, 'rrrrrrrr', '', NULL, 1, '2026-10-13 11:11:35'),
(6, 11, 'sfsdf', 'fsdfsdf', NULL, 6, '2026-10-13 11:21:08'),
(7, 12, 'fggfg', 'fgdfgdfgfg', 'https://www.youtube.com/watch?v=ZPWJm7UUDk8&list=RD-1AIyTn1Ab0&index=18', 1, '2026-10-13 13:20:30'),
(8, 12, '2', 'gdfgdfgsdf', 'https://www.youtube.com/watch?v=ZPWJm7UUDk8&list=RD-1AIyTn1Ab0&index=18', 2, '2026-10-13 13:20:49'),
(9, 12, 'dfjskdfjskdf', 'sdfsdfsdfsdfsdfsg', 'https://www.youtube.com/watch?v=ZPWJm7UUDk8&list=RD-1AIyTn1Ab0&index=18', 3, '2026-10-13 13:21:05'),
(10, 13, 'Bài 1 - Giới thiệu HTML', 'HTML là ngôn ngữ đánh dấu dùng để xây dựng cấu trúc trang Web.\r\n\r\nMột tài liệu HTML thường có các thành phần:\r\n- DOCTYPE: khai báo loại tài liệu.\r\n- html: phần tử gốc.\r\n- head: chứa thông tin về trang.\r\n- body: chứa nội dung hiển thị.\r\n\r\nMột số thẻ phổ biến:\r\n- h1: tiêu đề lớn.\r\n- p: đoạn văn.\r\n- a: liên kết.\r\n- img: hình ảnh.', '', 1, '2026-10-13 14:32:23'),
(11, 13, 'Bài 2 - Giới thiệu CSS', 'CSS là ngôn ngữ định kiểu dùng để thiết kế giao diện trang Web.\r\n\r\nCSS giúp thay đổi:\r\n- Màu chữ và màu nền.\r\n- Kích thước chữ.\r\n- Khoảng cách giữa các phần tử.\r\n- Bố cục trang Web.\r\n\r\nBa cách sử dụng CSS:\r\n1. Inline CSS: dùng thuộc tính style.\r\n2. Internal CSS: viết trong thẻ style.\r\n3. External CSS: liên kết file .css.\r\n\r\nVí dụ:\r\np { color: blue; }\r\n\r\nQuy tắc trên khiến văn bản trong thẻ p có màu xanh.', '', 2, '2026-10-13 14:32:23'),
(12, 13, 'Bài 3 - Giới thiệu JavaScript', 'JavaScript là ngôn ngữ lập trình giúp trang Web có khả năng tương tác.\r\n\r\nJavaScript thường được sử dụng để:\r\n- Xử lý sự kiện nhấn nút.\r\n- Kiểm tra dữ liệu biểu mẫu.\r\n- Thay đổi nội dung trang.\r\n- Gửi yêu cầu đến máy chủ.\r\n\r\nMột số khái niệm cơ bản:\r\n- let: khai báo biến có thể gán lại.\r\n- const: khai báo biến không thể gán lại.\r\n- function: định nghĩa hàm.\r\n- addEventListener: đăng ký xử lý sự kiện.\r\n\r\nVí dụ:\r\nlet x = 5;\r\nlet y = 3;\r\nconsole.log(x + y);\r\n\r\nKết quả là 8.', '', 3, '2026-10-13 14:32:24');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lesson_materials`
--

CREATE TABLE `lesson_materials` (
  `id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `file_type` enum('word','pdf','video') NOT NULL,
  `file_size` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `lesson_materials`
--

INSERT INTO `lesson_materials` (`id`, `lesson_id`, `file_name`, `stored_name`, `file_type`, `file_size`, `created_at`) VALUES
(3, 1, 'hd nhôm kính.docx', 'b045dc31e40f0b8d9ae0837e8ec0b377.docx', 'word', 31640, '2026-10-13 10:56:45'),
(4, 1, 'SOF203 - slide5.pdf', 'f7bacd24a03ab02ff5108d09141b93a6.pdf', 'pdf', 432672, '2026-10-13 10:57:00'),
(5, 4, 'nhom9_74DCTT23_XLA.docx', 'e8dfddd6db53b64e6c01ff9f40878910.docx', 'word', 3729486, '2026-10-13 11:11:57'),
(6, 6, 'Tiếng Anh Thuyết Trình.pdf', 'e1ce1e294daa9a352ed8d2d2271bf1be.pdf', 'pdf', 2863019, '2026-10-13 11:21:48');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lesson_progress`
--

CREATE TABLE `lesson_progress` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `lesson_progress`
--

INSERT INTO `lesson_progress` (`id`, `student_id`, `lesson_id`, `completed_at`) VALUES
(1, 1, 7, '2026-10-13 13:52:14');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `quizzes`
--

CREATE TABLE `quizzes` (
  `id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `quizzes`
--

INSERT INTO `quizzes` (`id`, `lesson_id`, `title`, `created_at`) VALUES
(1, 7, 'bài tập trắc nghiệm', '2026-10-13 13:21:25'),
(2, 10, 'Trắc nghiệm HTML cơ bản', '2026-10-13 14:32:24'),
(3, 11, 'Trắc nghiệm CSS cơ bản', '2026-10-13 14:32:24'),
(4, 12, 'Trắc nghiệm JavaScript cơ bản', '2026-10-13 14:32:24');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `quiz_answers`
--

CREATE TABLE `quiz_answers` (
  `id` int(11) NOT NULL,
  `attempt_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `selected_answer` enum('A','B','C','D') NOT NULL,
  `is_correct` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `quiz_answers`
--

INSERT INTO `quiz_answers` (`id`, `attempt_id`, `question_id`, `selected_answer`, `is_correct`) VALUES
(1, 1, 1, 'A', 0),
(2, 1, 2, 'A', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `quiz_attempts`
--

CREATE TABLE `quiz_attempts` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `correct_count` int(11) NOT NULL,
  `total_questions` int(11) NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `quiz_attempts`
--

INSERT INTO `quiz_attempts` (`id`, `quiz_id`, `student_id`, `correct_count`, `total_questions`, `score`, `submitted_at`) VALUES
(1, 1, 1, 1, 2, 5.00, '2026-10-13 13:36:13');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `quiz_questions`
--

CREATE TABLE `quiz_questions` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `option_a` text NOT NULL,
  `option_b` text NOT NULL,
  `option_c` text NOT NULL,
  `option_d` text NOT NULL,
  `correct_answer` enum('A','B','C','D') NOT NULL,
  `explanation` text NOT NULL,
  `question_order` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `quiz_questions`
--

INSERT INTO `quiz_questions` (`id`, `quiz_id`, `question_text`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_answer`, `explanation`, `question_order`) VALUES
(1, 1, '1+1', '1', '3', '2', '4', 'C', 'ngu thế cung ko biết', 1),
(2, 1, '2+2', '1', '2', '3', '4', 'A', 'chịu', 2),
(3, 2, 'Thẻ nào được dùng để tạo liên kết trong HTML?', 'img', 'table', 'a', 'button', 'C', 'Thẻ a tạo siêu liên kết. Thuộc tính href xác định địa chỉ mà trình duyệt sẽ điều hướng tới. Thẻ img dùng để hiển thị hình ảnh.', 3),
(4, 2, 'Thẻ HTML nào thường dùng để tạo tiêu đề cấp cao nhất?', 'p', 'h1', 'span', 'div', 'B', 'Thẻ h1 biểu thị tiêu đề cấp cao nhất trong hệ thống các thẻ h1 đến h6. Thẻ p dùng cho đoạn văn, còn span và div chủ yếu dùng để nhóm nội dung.', 2),
(5, 2, 'HTML là viết tắt của cụm từ nào?', 'Hyper Text Markup Language', 'High Text Machine Language', 'Hyper Tool Markup Language', 'Home Text Markup Language', 'A', 'HTML là viết tắt của Hyper Text Markup Language, nghĩa là ngôn ngữ đánh dấu siêu văn bản. HTML dùng các thẻ để mô tả cấu trúc nội dung như tiêu đề, đoạn văn, hình ảnh và liên kết.', 1),
(6, 3, 'Cú pháp CSS nào đúng để đặt màu chữ xanh cho thẻ p?', 'p { color: blue; }', 'p = color: blue;', 'p (color = blue)', 'p:color(blue)', 'A', 'Một quy tắc CSS có dạng selector { property: value; }. Ở đây p là bộ chọn, color là thuộc tính, blue là giá trị. Vì vậy p { color: blue; } là cú pháp đúng.', 3),
(7, 3, 'Thuộc tính CSS nào thay đổi màu chữ?', 'background', 'font-size', 'color', 'padding', 'C', 'Thuộc tính color xác định màu chữ. background dùng cho nền, font-size điều chỉnh kích thước chữ và padding tạo khoảng cách bên trong phần tử.', 2),
(8, 3, 'CSS được sử dụng chủ yếu để làm gì?', 'Lưu dữ liệu', 'Thiết kế giao diện', 'Quản lý máy chủ', 'Tạo cơ sở dữ liệu', 'B', 'CSS dùng để định kiểu các phần tử HTML, bao gồm màu sắc, kiểu chữ, kích thước, khoảng cách và bố cục. HTML xây dựng cấu trúc, còn CSS quyết định cách trình bày.', 1),
(9, 4, 'Kết quả của phép tính 5 + 3 trong JavaScript là bao nhiêu?', '2', '8', '15', '53', 'B', 'Khi cả hai toán hạng đều là số, toán tử + thực hiện phép cộng số học. Vì vậy 5 + 3 bằng 8. Nếu cả hai giá trị là chuỗi, toán tử + có thể thực hiện nối chuỗi.', 3),
(10, 4, 'Từ khóa nào khai báo biến có thể gán lại trong JavaScript?', 'const', 'let', 'class', 'return', 'B', 'let được dùng để khai báo biến và cho phép gán lại giá trị. const khai báo ràng buộc không thể gán lại, class dùng để khai báo lớp và return dùng để trả kết quả từ hàm.', 2),
(11, 4, 'JavaScript thường được sử dụng để làm gì trên trang Web?', 'Chỉ tạo cấu trúc HTML', 'Chỉ chỉnh màu sắc', 'Tạo tương tác và xử lý sự kiện', 'Thay thế hoàn toàn cơ sở dữ liệu', 'C', 'JavaScript cho phép xử lý hành động của người dùng như nhấn nút, nhập biểu mẫu, thay đổi nội dung trang và gửi yêu cầu bất đồng bộ. HTML tạo cấu trúc, CSS tạo kiểu, JavaScript xử lý hành vi.', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('student','teacher','admin') NOT NULL DEFAULT 'student',
  `status` enum('active','locked') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `role`, `status`, `created_at`) VALUES
(1, 'Nguyễn Văn Test', 'teststudent@example.com', '$2y$10$bl1Co037e/7li/APZszfd.qx4MwwFRBfk2KYftKRfYmBfWxM9nZVO', 'student', 'active', '2026-10-12 10:24:47'),
(2, 'Nguyễn Văn Giảng Viên', 'teacher@example.com', '$2y$10$R7ePdgvxnx8ZN8iB/wrUNegSOBoIaHYSwaDfNQYONDxat6ZdbYWTO', 'teacher', 'active', '2026-10-12 10:51:45'),
(3, 'Quản trị viên EduSpace', 'admin@example.com', '$2y$10$mSRFwOavu3LxJP5sOaUOK.sGxRrsd1.UE0aCpuvt2ZvzIaalnTKDi', 'admin', 'active', '2026-10-12 10:53:07'),
(4, 'Nguyễn Văn Giảng Viên 2', 'teacher2@example.com', '$2y$10$RpLCyD67sCeMp6i.vggYdOMg.wo2DQgqHGuchDDYVFd5xdgi4G9nG', 'teacher', 'active', '2026-10-12 15:47:46');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_course` (`student_id`,`course_id`),
  ADD KEY `idx_enrollments_course` (`course_id`);

--
-- Chỉ mục cho bảng `lessons`
--
ALTER TABLE `lessons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_lessons_course` (`course_id`);

--
-- Chỉ mục cho bảng `lesson_materials`
--
ALTER TABLE `lesson_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_materials_lesson` (`lesson_id`);

--
-- Chỉ mục cho bảng `lesson_progress`
--
ALTER TABLE `lesson_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_student_lesson` (`student_id`,`lesson_id`),
  ADD KEY `fk_progress_lesson` (`lesson_id`);

--
-- Chỉ mục cho bảng `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lesson_id` (`lesson_id`);

--
-- Chỉ mục cho bảng `quiz_answers`
--
ALTER TABLE `quiz_answers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_attempt_question` (`attempt_id`,`question_id`),
  ADD KEY `fk_answer_question` (`question_id`);

--
-- Chỉ mục cho bảng `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_quiz_student` (`quiz_id`,`student_id`),
  ADD KEY `fk_attempt_student` (`student_id`);

--
-- Chỉ mục cho bảng `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_question_quiz` (`quiz_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT cho bảng `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `lessons`
--
ALTER TABLE `lessons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `lesson_materials`
--
ALTER TABLE `lesson_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `lesson_progress`
--
ALTER TABLE `lesson_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `quiz_answers`
--
ALTER TABLE `quiz_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `fk_enrollments_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`),
  ADD CONSTRAINT `fk_enrollments_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `lessons`
--
ALTER TABLE `lessons`
  ADD CONSTRAINT `fk_lessons_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`);

--
-- Các ràng buộc cho bảng `lesson_materials`
--
ALTER TABLE `lesson_materials`
  ADD CONSTRAINT `fk_material_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `lesson_progress`
--
ALTER TABLE `lesson_progress`
  ADD CONSTRAINT `fk_progress_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`),
  ADD CONSTRAINT `fk_progress_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `quizzes`
--
ALTER TABLE `quizzes`
  ADD CONSTRAINT `fk_quiz_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`);

--
-- Các ràng buộc cho bảng `quiz_answers`
--
ALTER TABLE `quiz_answers`
  ADD CONSTRAINT `fk_answer_attempt` FOREIGN KEY (`attempt_id`) REFERENCES `quiz_attempts` (`id`),
  ADD CONSTRAINT `fk_answer_question` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`id`);

--
-- Các ràng buộc cho bảng `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD CONSTRAINT `fk_attempt_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`),
  ADD CONSTRAINT `fk_attempt_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD CONSTRAINT `fk_question_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
