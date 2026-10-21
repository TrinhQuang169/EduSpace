
<?php
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/course_validation.php";

requireRole("teacher");

// 1. Chỉ chấp nhận yêu cầu POST
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

// 3. Kiểm tra ID lớp học
$courseId = filter_input(
    INPUT_POST,
    "course_id",
    FILTER_VALIDATE_INT
);

if (!$courseId || $courseId < 1) {
    http_response_code(400);
    exit("ID lớp học không hợp lệ.");
}

$teacherId = (int)$_SESSION["user_id"];

try {
    // 4. Bắt đầu transaction
    $pdo->beginTransaction();

    // 5. Khóa và kiểm tra lớp học thuộc giáo viên
    $stmt = $pdo->prepare(
        "SELECT id, status
         FROM courses
         WHERE id = ?
           AND teacher_id = ?
         FOR UPDATE"
    );

    $stmt->execute([$courseId, $teacherId]);
    $course = $stmt->fetch();

    if (!$course) {
        $pdo->rollBack();

        http_response_code(404);
        exit("Không tìm thấy lớp học của bạn.");
    }

    // 6. Chỉ draft/rejected mới được gửi duyệt
    if (!in_array(
        $course["status"],
        ["draft", "rejected"],
        true
    )) {
        $pdo->rollBack();

        http_response_code(409);
        exit(
            "Không thể gửi duyệt. " .
            "Lớp học đang chờ duyệt hoặc đã được duyệt."
        );
    }

    // 7. KIỂM TRA NỘI DUNG LỚP HỌC
    $result = validateCourseContent($pdo, $courseId);

    if (!$result["ok"]) {
        $pdo->rollBack();

        http_response_code(422);

        exit(
            "Chưa thể gửi duyệt: " .
            htmlspecialchars(
                $result["message"],
                ENT_QUOTES,
                "UTF-8"
            )
        );
    }

    // 8. Nội dung hợp lệ mới chuyển sang pending
    $update = $pdo->prepare(
        "UPDATE courses
         SET status = 'pending'
         WHERE id = ?
           AND teacher_id = ?
           AND status IN ('draft', 'rejected')"
    );

    $update->execute([$courseId, $teacherId]);

    if ($update->rowCount() !== 1) {
        throw new RuntimeException(
            "Không thể cập nhật trạng thái lớp học."
        );
    }

    // 9. Lưu thay đổi
    $pdo->commit();

    // 10. Trở về danh sách lớp học
    header("Location: courses.php?message=submitted");
    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    http_response_code(500);
    exit("Có lỗi khi gửi duyệt lớp học. Vui lòng thử lại.");
}
