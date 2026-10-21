
<?php
require_once __DIR__ . "/../includes/auth.php";

requireRole("teacher");

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

// 3. Lấy ID lớp học
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

    // 5. Kiểm tra quyền và khóa bản ghi lớp học
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

    // Chỉ được xóa bản nháp
    if ($course["status"] !== "draft") {
        $pdo->rollBack();

        http_response_code(409);
        exit(
            "Chỉ được xóa lớp học ở trạng thái bản nháp."
        );
    }

    // 6. Kiểm tra lớp học còn bài học không
    $countStmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM lessons
         WHERE course_id = ?"
    );

    $countStmt->execute([$courseId]);
    $lessonCount = (int)$countStmt->fetchColumn();

    if ($lessonCount > 0) {
        $pdo->rollBack();

        http_response_code(409);
        exit(
            "Không thể xóa lớp học vì vẫn còn " .
            $lessonCount . " bài học. " .
            "Hãy xóa tài liệu và các bài học trước."
        );
    }

    // 7. Lớp học không còn bài học -> được xóa
    $delete = $pdo->prepare(
        "DELETE FROM courses
         WHERE id = ?
           AND teacher_id = ?
           AND status = 'draft'"
    );

    $delete->execute([$courseId, $teacherId]);

    if ($delete->rowCount() !== 1) {
        throw new RuntimeException(
            "Không thể xóa lớp học."
        );
    }

    // 8. Hoàn thành
    $pdo->commit();

    header("Location: courses.php?message=deleted");
    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    http_response_code(500);
    exit("Có lỗi khi xóa lớp học. Vui lòng thử lại.");
}
