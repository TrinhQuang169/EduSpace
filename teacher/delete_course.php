
<?php
require_once __DIR__ . "/../includes/auth.php";
requireRole("teacher");

// Chỉ chấp nhận yêu cầu POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    header("Allow: POST");
    exit("Phương thức không hợp lệ.");
}

// Kiểm tra CSRF token
$token = $_POST["csrf_token"] ?? "";

if (
    !isset($_SESSION["csrf_token"]) ||
    !is_string($token) ||
    !hash_equals($_SESSION["csrf_token"], $token)
) {
    http_response_code(403);
    exit("Yêu cầu không hợp lệ.");
}

// Lấy ID khóa học
$courseId = filter_input(
    INPUT_POST,
    "course_id",
    FILTER_VALIDATE_INT
);

if (!$courseId || $courseId < 1) {
    http_response_code(400);
    exit("ID khóa học không hợp lệ.");
}

// Lấy ID giảng viên từ Session
$teacherId = (int)$_SESSION["user_id"];

// Chỉ xóa bản nháp thuộc giảng viên đang đăng nhập
$sql = "DELETE FROM courses
        WHERE id = ?
          AND teacher_id = ?
          AND status = 'draft'";

$stmt = $pdo->prepare($sql);
$stmt->execute([$courseId, $teacherId]);

if ($stmt->rowCount() !== 1) {
    http_response_code(409);
    exit(
        "Không thể xóa khóa học. " .
        "Khóa học không thuộc quyền quản lý của bạn " .
        "hoặc không ở trạng thái bản nháp."
    );
}

// Xóa thành công
header("Location: courses.php?message=deleted");
exit;
