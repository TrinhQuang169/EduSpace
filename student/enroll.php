
<?php
require_once __DIR__ . "/../includes/auth.php";

requireRole("student");

// 1. Chỉ chấp nhận POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    header("Allow: POST");
    exit("Phương thức không hợp lệ.");
}

// 2. Kiểm tra CSRF
$token = $_POST["csrf_token"] ?? "";

if (
    !isset($_SESSION["csrf_token"]) ||
    !is_string($token) ||
    !hash_equals($_SESSION["csrf_token"], $token)
) {
    http_response_code(403);
    exit("Yêu cầu không hợp lệ.");
}

// 3. Lấy ID khóa học
$courseId = filter_input(
    INPUT_POST,
    "course_id",
    FILTER_VALIDATE_INT
);

if (!$courseId || $courseId < 1) {
    http_response_code(400);
    exit("ID khóa học không hợp lệ.");
}

$studentId = (int)$_SESSION["user_id"];

try {
    $pdo->beginTransaction();

    // 4. Chỉ chấp nhận khóa học đã được duyệt
    $check = $pdo->prepare(
        "SELECT id, status
         FROM courses
         WHERE id = ?
         FOR UPDATE"
    );

    $check->execute([$courseId]);
    $course = $check->fetch(PDO::FETCH_ASSOC);

    if (!$course || $course["status"] !== "approved") {
        $pdo->rollBack();

        http_response_code(403);
        exit(
            "Khóa học không tồn tại hoặc chưa được phê duyệt."
        );
    }

    // 5. Thử ghi thông tin đăng ký
    // UNIQUE KEY trong database ngăn đăng ký trùng
    $insert = $pdo->prepare(
        "INSERT INTO enrollments
            (student_id, course_id)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE id = id"
    );

    $insert->execute([$studentId, $courseId]);

    // MySQL trả 1 nếu thêm mới, 0 nếu đã tồn tại
    $inserted = $insert->rowCount() === 1;

    $pdo->commit();

    // 6. Quay lại danh sách khóa học
    $message = $inserted ? "enrolled" : "already";

    header(
        "Location: courses.php?message=" . $message
    );
    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    http_response_code(500);
    exit("Không thể đăng ký khóa học. Vui lòng thử lại.");
}
