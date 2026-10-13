
<?php
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../config/storage.php";

requireRole("teacher");

// Chỉ chấp nhận POST
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

$materialId = filter_input(
    INPUT_POST,
    "material_id",
    FILTER_VALIDATE_INT
);

$lessonId = filter_input(
    INPUT_POST,
    "lesson_id",
    FILTER_VALIDATE_INT
);

if (!$materialId || !$lessonId) {
    http_response_code(400);
    exit("ID tài liệu hoặc bài học không hợp lệ.");
}

$teacherId = (int)$_SESSION["user_id"];

$pdo->beginTransaction();

try {
    // Tìm khóa học chứa bài học này
    $stmt = $pdo->prepare(
        "SELECT c.id, c.status
         FROM courses c
         JOIN lessons l ON l.course_id = c.id
         WHERE l.id = ?
           AND c.teacher_id = ?
         FOR UPDATE"
    );

    $stmt->execute([$lessonId, $teacherId]);
    $course = $stmt->fetch();

    if (!$course || !in_array(
        $course["status"],
        ["draft", "rejected"],
        true
    )) {
        throw new RuntimeException(
            "Không có quyền xóa tài liệu này."
        );
    }

    // Khóa bản ghi tài liệu cần xóa
    $stmt = $pdo->prepare(
        "SELECT id, stored_name
         FROM lesson_materials
         WHERE id = ? AND lesson_id = ?
         FOR UPDATE"
    );

    $stmt->execute([$materialId, $lessonId]);
    $material = $stmt->fetch();

    if (!$material) {
        throw new RuntimeException(
            "Không tìm thấy tài liệu."
        );
    }

    $storedName = $material["stored_name"];

    // Chặn đường dẫn không đúng định dạng do PHP tạo ra
    if (!preg_match(
        '/^[a-f0-9]{32}\.(docx|pdf|mp4)$/',
        $storedName
    )) {
        throw new RuntimeException(
            "Tên file lưu trữ không hợp lệ."
        );
    }

    $filePath = rtrim(
        EDUSPACE_STORAGE_PATH,
        "/\\"
    ) . DIRECTORY_SEPARATOR . $storedName;

    // Xóa bản ghi trong MySQL
    $delete = $pdo->prepare(
        "DELETE FROM lesson_materials
         WHERE id = ? AND lesson_id = ?"
    );

    $delete->execute([$materialId, $lessonId]);

    if ($delete->rowCount() !== 1) {
        throw new RuntimeException(
            "Không thể xóa bản ghi tài liệu."
        );
    }

    $pdo->commit();

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    http_response_code(400);
    exit("Không thể xóa tài liệu. Hãy kiểm tra quyền và trạng thái khóa học.");
}

// Sau khi DB lưu thành công mới xóa file vật lý.
// Nếu ổ đĩa bị lỗi, không xóa nhầm file khác.
if (is_file($filePath)) {
    if (!unlink($filePath)) {
        error_log(
            "EduSpace: Khong the xoa file " . $storedName
        );

        // DB đã xóa nhưng file còn lại cần dọn thủ công.
        http_response_code(500);
        exit(
            "Đã xóa bản ghi nhưng chưa xóa được file trên ổ đĩa. " .
            "Hãy kiểm tra nhật ký máy chủ."
        );
    }
}

header(
    "Location: materials.php?lesson_id=" .
    $lessonId . "&message=deleted"
);
exit;
