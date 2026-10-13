
<?php
require_once __DIR__ . "/../includes/auth.php";
requireRole("teacher");

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

$lessonId = filter_input(
    INPUT_POST,
    "lesson_id",
    FILTER_VALIDATE_INT
);

$courseId = filter_input(
    INPUT_POST,
    "course_id",
    FILTER_VALIDATE_INT
);

if (!$lessonId || !$courseId) {
    http_response_code(400);
    exit("ID không hợp lệ.");
}

$teacherId = (int)$_SESSION["user_id"];

try {
    $pdo->beginTransaction();

    // Khóa khóa học để bảo đảm chưa được gửi duyệt
    $check = $pdo->prepare(
        "SELECT status
         FROM courses
         WHERE id = ? AND teacher_id = ?
         FOR UPDATE"
    );

    $check->execute([$courseId, $teacherId]);
    $course = $check->fetch();

    if (!$course || !in_array(
        $course["status"],
        ["draft", "rejected"],
        true
    )) {
        $pdo->rollBack();
        http_response_code(403);
        exit("Không có quyền xóa bài học này.");
    }

    // Không xóa bài học nếu vẫn còn tài liệu
    $count = $pdo->prepare(
        "SELECT COUNT(*)
         FROM lesson_materials
         WHERE lesson_id = ?"
    );

    $count->execute([$lessonId]);
    $materialCount = (int)$count->fetchColumn();

    if ($materialCount > 0) {
        $pdo->rollBack();
        http_response_code(409);
        exit(
            "Bài học còn tài liệu đính kèm. " .
            "Hãy xóa tài liệu trước."
        );
    }

    $delete = $pdo->prepare(
        "DELETE FROM lessons
         WHERE id = ? AND course_id = ?"
    );

    $delete->execute([$lessonId, $courseId]);

    if ($delete->rowCount() !== 1) {
        $pdo->rollBack();
        http_response_code(404);
        exit("Không tìm thấy bài học.");
    }

    $pdo->commit();

    header(
        "Location: lessons.php?course_id=" .
        $courseId . "&message=deleted"
    );
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    http_response_code(500);
    exit("Không thể xóa bài học.");
}
