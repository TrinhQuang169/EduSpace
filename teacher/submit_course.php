
<?php
require_once __DIR__ . "/../includes/auth.php";
requireRole("teacher");

// Chỉ xử lý yêu cầu POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    header("Allow: POST");
    exit("Phương thức không hợp lệ.");
}

// Kiểm tra CSRF
$token = $_POST["csrf_token"] ?? "";

if (
    !isset($_SESSION["csrf_token"]) ||
    !is_string($token) ||
    !hash_equals($_SESSION["csrf_token"], $token)
) {
    http_response_code(403);
    exit("Yêu cầu không hợp lệ.");
}

// Kiểm tra ID khóa học
$courseId = filter_input(
    INPUT_POST,
    "course_id",
    FILTER_VALIDATE_INT
);

if (!$courseId || $courseId < 1) {
    http_response_code(400);
    exit("ID khóa học không hợp lệ.");
}

$teacherId = (int)$_SESSION["user_id"];

// Chỉ gửi duyệt khóa học của chính giảng viên
// và đang ở trạng thái draft hoặc rejected
$sql = "UPDATE courses
        SET status = 'pending'
        WHERE id = ?
          AND teacher_id = ?
          AND status IN ('draft', 'rejected')";

$stmt = $pdo->prepare($sql);
$stmt->execute([$courseId, $teacherId]);

if ($stmt->rowCount() !== 1) {
    http_response_code(409);
    exit(
        "Không thể gửi duyệt. Khóa học không thuộc quyền " .
        "quản lý của bạn hoặc trạng thái không hợp lệ."
    );
}

// Gửi duyệt thành công
header("Location: courses.php?message=submitted");
exit;
