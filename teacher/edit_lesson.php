
<?php
require_once __DIR__ . "/../includes/auth.php";
requireRole("teacher");

$lessonId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$lessonId || $lessonId < 1) {
    http_response_code(400);
    exit("ID bài học không hợp lệ.");
}

$teacherId = (int)$_SESSION["user_id"];

// Lấy bài học thuộc khóa học của giảng viên
$stmt = $pdo->prepare(
    "SELECT l.*, c.title AS course_title, c.status
     FROM lessons l
     JOIN courses c ON c.id = l.course_id
     WHERE l.id = ? AND c.teacher_id = ?
     LIMIT 1"
);

$stmt->execute([$lessonId, $teacherId]);
$lesson = $stmt->fetch();

if (!$lesson) {
    http_response_code(404);
    exit("Không tìm thấy bài học của bạn.");
}

if (!in_array(
    $lesson["status"],
    ["draft", "rejected"],
    true
)) {
    http_response_code(403);
    exit("Khóa học đang chờ duyệt hoặc đã duyệt.");
}

$courseId = (int)$lesson["course_id"];

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$error = "";

$title = $lesson["title"];
$content = $lesson["content"] ?? "";
$videoUrl = $lesson["video_url"] ?? "";
$lessonOrder = (int)$lesson["lesson_order"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        http_response_code(403);
        exit("Yêu cầu không hợp lệ.");
    }

    $title = trim($_POST["title"] ?? "");
    $content = trim($_POST["content"] ?? "");
    $videoUrl = trim($_POST["video_url"] ?? "");

    $lessonOrder = filter_input(
        INPUT_POST,
        "lesson_order",
        FILTER_VALIDATE_INT
    );

    if ($title === "") {
        $error = "Vui lòng nhập tên bài học.";
    } elseif (mb_strlen($title) > 255) {
        $error = "Tên bài học không được quá 255 ký tự.";
    } elseif (!$lessonOrder || $lessonOrder < 1) {
        $error = "Thứ tự bài học phải lớn hơn 0.";
    } elseif (mb_strlen($content) > 50000) {
        $error = "Nội dung bài học quá dài.";
    } elseif (strlen($videoUrl) > 500) {
        $error = "Đường dẫn video quá dài.";
    } elseif ($videoUrl !== "" && (
        !filter_var($videoUrl, FILTER_VALIDATE_URL) ||
        strtolower((string)parse_url($videoUrl, PHP_URL_SCHEME))
            !== "https" ||
        !in_array(
            strtolower((string)parse_url($videoUrl, PHP_URL_HOST)),
            ["youtube.com", "www.youtube.com", "youtu.be"],
            true
        )
    )) {
        $error = "Vui lòng nhập liên kết HTTPS YouTube hợp lệ.";
    } else {

        try {
            $pdo->beginTransaction();

            // Kiểm tra trạng thái ngay trước lúc cập nhật
            $lock = $pdo->prepare(
                "SELECT status
                 FROM courses
                 WHERE id = ? AND teacher_id = ?
                 FOR UPDATE"
            );

            $lock->execute([$courseId, $teacherId]);
            $current = $lock->fetch();

            if (!$current || !in_array(
                $current["status"],
                ["draft", "rejected"],
                true
            )) {
                $pdo->rollBack();
                http_response_code(409);
                exit("Khóa học đã thay đổi trạng thái.");
            }

            $update = $pdo->prepare(
                "UPDATE lessons
                 SET title = ?,
                     content = ?,
                     video_url = ?,
                     lesson_order = ?
                 WHERE id = ? AND course_id = ?"
            );

            $update->execute([
                $title,
                $content,
                $videoUrl !== "" ? $videoUrl : null,
                $lessonOrder,
                $lessonId,
                $courseId
            ]);

            $pdo->commit();

            header(
                "Location: lessons.php?course_id=" .
                $courseId . "&message=updated"
            );
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());
            $error = "Không thể cập nhật bài học.";
        }
    }
}

function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Sửa bài học - EduSpace</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page">

<div class="auth-container">
    <div class="auth-card">

        <h1>Chỉnh sửa bài học</h1>

        <p class="auth-subtitle">
            Khóa học:
            <?php echo e($lesson["course_title"]); ?>
        </p>

        <?php if ($error !== ""): ?>
            <div class="alert alert-error">
                <?php echo e($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo e($_SESSION["csrf_token"]); ?>"
            >

            <div class="form-group">
                <label for="title">Tên bài học</label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    maxlength="255"
                    value="<?php echo e($title); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="content">Nội dung bài học</label>
                <textarea
                    id="content"
                    name="content"
                    rows="8"
                ><?php echo e($content); ?></textarea>
            </div>

            <div class="form-group">
                <label for="video_url">
                    Video YouTube (không bắt buộc)
                </label>
                <input
                    type="url"
                    id="video_url"
                    name="video_url"
                    maxlength="500"
                    value="<?php echo e($videoUrl); ?>"
                >
            </div>

            <div class="form-group">
                <label for="lesson_order">
                    Thứ tự bài học
                </label>
                <input
                    type="number"
                    id="lesson_order"
                    name="lesson_order"
                    min="1"
                    value="<?php echo e($lessonOrder); ?>"
                    required
                >
            </div>

            <button
                type="submit"
                class="btn btn-primary auth-submit"
            >
                Lưu thay đổi
            </button>
        </form>

        <p class="auth-back">
            <a href="lessons.php?course_id=<?php echo $courseId; ?>">
                ← Quay lại danh sách bài học
            </a>
        </p>

    </div>
</div>

</body>
</html>
