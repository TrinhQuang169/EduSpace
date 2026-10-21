
<?php
require_once __DIR__ . "/../includes/auth.php";
requireRole("admin");

// Chỉ cho phép POST
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

// Lấy dữ liệu từ biểu mẫu
$courseId = filter_input(
    INPUT_POST,
    "course_id",
    FILTER_VALIDATE_INT
);

$action = $_POST["action"] ?? "";

if (!$courseId || $courseId < 1) {
    http_response_code(400);
    exit("ID lớp học không hợp lệ.");
}

if (!in_array($action, ["approve", "reject"], true)) {
    http_response_code(400);
    exit("Thao tác không hợp lệ.");
}

// Xác định trạng thái mới
$newStatus = $action === "approve"
    ? "approved"
    : "rejected";

// Chỉ cập nhật lớp học đang chờ duyệt
$stmt = $pdo->prepare(
    "UPDATE courses
     SET status = ?
     WHERE id = ?
       AND status = 'pending'"
);

$stmt->execute([$newStatus, $courseId]);

if ($stmt->rowCount() !== 1) {
    http_response_code(409);
    exit(
        "Không thể xử lý. Lớp học không tồn tại " .
        "hoặc không còn ở trạng thái chờ duyệt."
    );
}

// Xử lý thành công
$message = $action === "approve"
    ? "approved"
    : "rejected";

header("Location: courses.php?message=" . $message);
exit;
