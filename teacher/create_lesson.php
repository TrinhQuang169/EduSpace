
<?php
require_once __DIR__ . "/../includes/auth.php";
requireRole("teacher");

// Lấy ID lớp học từ URL
$courseId = filter_input(
    INPUT_GET,
    "course_id",
    FILTER_VALIDATE_INT
);

if (!$courseId || $courseId < 1) {
    http_response_code(400);
    exit("ID lớp học không hợp lệ.");
}

$teacherId = (int)$_SESSION["user_id"];

// Kiểm tra lớp học có thuộc giáo viên không
$stmt = $pdo->prepare(
    "SELECT id, title, status
     FROM courses
     WHERE id = ? AND teacher_id = ?
     LIMIT 1"
);

$stmt->execute([$courseId, $teacherId]);
$course = $stmt->fetch();

if (!$course) {
    http_response_code(404);
    exit("Không tìm thấy lớp học của bạn.");
}

// Chỉ cho thêm bài học trước khi được phê duyệt
if (!in_array($course["status"], ["draft", "rejected"], true)) {
    http_response_code(403);
    exit("Lớp học đang chờ duyệt hoặc đã duyệt, không thể thêm bài học.");
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$error = "";
$title = "";
$content = "";
$videoUrl = "";
$lessonOrder = 1;

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
        $error = "Tên bài học quá dài.";

    } elseif (!$lessonOrder || $lessonOrder < 1) {
        $error = "Thứ tự bài học phải lớn hơn 0.";

    } elseif (strlen($videoUrl) > 500) {
        $error = "Đường dẫn video quá dài.";

    } elseif (
        $videoUrl !== "" &&
        (
            !filter_var($videoUrl, FILTER_VALIDATE_URL) ||
            !in_array(
                strtolower(parse_url($videoUrl, PHP_URL_SCHEME) ?? ""),
                ["https"],
                true
            ) ||
            !in_array(
                strtolower(parse_url($videoUrl, PHP_URL_HOST) ?? ""),
                ["youtube.com", "www.youtube.com", "youtu.be"],
                true
            )
        )
    ) {
        $error = "Vui lòng nhập link HTTPS YouTube hợp lệ.";

    } else {
        // Kiểm tra lại trạng thái trong lúc ghi dữ liệu
        // và khóa bản ghi lớp học trong giao dịch
        try {
            $pdo->beginTransaction();

            $lock = $pdo->prepare(
                "SELECT status
                 FROM courses
                 WHERE id = ? AND teacher_id = ?
                 FOR UPDATE"
            );

            $lock->execute([$courseId, $teacherId]);
            $current = $lock->fetch();

            if (
                !$current ||
                !in_array(
                    $current["status"],
                    ["draft", "rejected"],
                    true
                )
            ) {
                $pdo->rollBack();
                http_response_code(409);
                exit("Trạng thái lớp học đã thay đổi.");
            }

            $insert = $pdo->prepare(
                "INSERT INTO lessons
                 (course_id, title, content,
                  video_url, lesson_order)
                 VALUES (?, ?, ?, ?, ?)"
            );

            $insert->execute([
                $courseId,
                $title,
                $content,
                $videoUrl !== "" ? $videoUrl : null,
                $lessonOrder
            ]);

            $pdo->commit();

            header(
                "Location: lessons.php?course_id=" .
                $courseId .
                "&message=created"
            );
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());
            $error = "Không thể lưu bài học. Vui lòng thử lại.";
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

<?php require_once __DIR__ . "/../includes/ui.php"; eduStart('Thêm bài học', 'courses'); ?>


    <div class="edu-form-card">

        <h1>Thêm bài học mới</h1>

        <p class="auth-subtitle">
            Lớp học:
            <?php echo e($course["title"]); ?>
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
                <label for="field-title">Tên bài học</label>
<input id="field-title"
                    type="text"
                    name="title"
                    maxlength="255"
                    value="<?php echo e($title); ?>"
                    placeholder="Ví dụ: Giới thiệu PHP"
                    required
                >
            </div>

            <div class="form-group">
                <label for="field-content">Nội dung bài học</label>
<textarea id="field-content"
                    name="content"
                    rows="8"
                    placeholder="Nhập nội dung bài học..."
                ><?php echo e($content); ?></textarea>
            </div>

            <div class="form-group">
                <label for="field-video_url">Link video YouTube (nếu có)</label>
<input id="field-video_url"
                    type="url"
                    name="video_url"
                    maxlength="500"
                    value="<?php echo e($videoUrl); ?>"
                    placeholder="https://www.youtube.com/watch?v=..."
                >
            </div>

            <div class="form-group">
                <label for="field-lesson_order">Thứ tự bài học</label>
<input id="field-lesson_order"
                    type="number"
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
                Lưu bài học
            </button>
        </form>

        <p class="auth-back">
            <a href="lessons.php?course_id=<?php echo $courseId; ?>">
                ← Quay lại danh sách bài học
            </a>
        </p>

    </div>
<?php eduEnd(); ?>
