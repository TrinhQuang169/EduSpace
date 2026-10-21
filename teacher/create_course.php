
<?php
require_once __DIR__ . "/../includes/auth.php";
requireRole("teacher");
require_once __DIR__ . '/../includes/classes.php';
$classesReady = eduPrepareClasses($pdo);

$error = "";
$success = "";

$title = "";
$description = "";
$category = "";
$icon = "📚";
$color = "blue";

$allowedColors = [
    "blue", "purple", "yellow",
    "green", "orange", "pink"
];

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        http_response_code(403);
        exit("Yêu cầu không hợp lệ.");
    }

    if (!$classesReady) { http_response_code(503); exit(eduClassMigrationMessage()); }

    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $icon = trim($_POST["icon"] ?? "📚");
    $color = $_POST["color"] ?? "blue";

    if ($title === "" || $category === "") {
        $error = "Vui lòng nhập tên và danh mục lớp học.";

    } elseif (mb_strlen($title) > 255) {
        $error = "Tên lớp học quá dài.";

    } elseif (mb_strlen($category) > 100) {
        $error = "Tên danh mục quá dài.";

    } elseif (mb_strlen($description) > 5000) {
        $error = "Mô tả không được vượt quá 5000 ký tự.";

    } elseif (!in_array($color, $allowedColors, true)) {
        $error = "Màu lớp học không hợp lệ.";

    } else {
        // Lấy ID giáo viên từ Session
        $teacherId = (int)$_SESSION["user_id"];
        $teacherName = $_SESSION["user_name"];

        $sql = "INSERT INTO courses
                (teacher_id, title, description,
                 category, icon, color,
                 teacher_name, students, status, class_code)
                VALUES (?, ?, ?, ?, ?, ?, ?, 0, 'draft', ?)";

        $stmt = $pdo->prepare($sql);

        try {
            for ($codeRetry = 0; $codeRetry < 5; $codeRetry++) {
                $classCode = eduGenerateClassCode();
                try {
                    $stmt->execute([$teacherId, $title, $description, $category, $icon, $color, $teacherName, $classCode]);
                    break;
                } catch (PDOException $exception) {
                    if ((int)($exception->errorInfo[1] ?? 0) !== 1062 || $codeRetry === 4) throw $exception;
                }
            }
        } catch (Throwable $exception) {
            error_log('Class creation failed: ' . $exception->getMessage());
            $error = 'Không thể tạo lớp học. Vui lòng thử lại.';
        }
        if ($error === '') {
            $success = "Tạo lớp học thành công!";

            $title = "";
            $description = "";
            $category = "";
            $icon = "📚";
            $color = "blue";
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

<?php require_once __DIR__ . "/../includes/ui.php"; eduStart('Tạo lớp học mới', 'create'); ?>


    <div class="edu-form-card">



        <h1>Tạo lớp học mới</h1><?php if (!$classesReady): ?><div class="alert alert-error"><?= e(eduClassMigrationMessage()) ?></div><?php endif; ?>

        <p class="auth-subtitle">
            Thêm lớp học vào hệ thống. Mã tham gia riêng sẽ được tạo tự động.
        </p>

        <?php if ($error !== ""): ?>
            <div class="alert alert-error">
                <?php echo e($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success !== ""): ?>
            <div class="alert alert-success">
                <?php echo e($success); ?><p>Mã tham gia: <code class="edu-class-code"><?= e($classCode) ?></code></p><a href="courses.php">Đến danh sách lớp học →</a>
            </div>
        <?php endif; ?>

        <form method="POST">

            <input type="hidden"
                   name="csrf_token"
                   value="<?php echo e(
                       $_SESSION["csrf_token"]
                   ); ?>">

            <div class="form-group">
                <label for="field-title">Tên lớp học</label>
<input id="field-title" type="text"
                       name="title"
                       maxlength="255"
                       value="<?php echo e($title); ?>"
                       placeholder="Ví dụ: PHP nâng cao"
                       required>
            </div>

            <div class="form-group">
                <label for="field-description">Mô tả lớp học</label>
<textarea id="field-description" name="description"
                          rows="5"
                          maxlength="5000"
                          placeholder="Giới thiệu lớp học..."><?php
                    echo e($description);
                ?></textarea>
            </div>

            <div class="form-group">
                <label for="field-category">Danh mục</label>
<input id="field-category" type="text"
                       name="category"
                       maxlength="100"
                       value="<?php echo e($category); ?>"
                       placeholder="Ví dụ: Lập trình"
                       required>
            </div>

            <div class="form-group">
                <label for="field-icon">Biểu tượng</label>
<select id="field-icon" name="icon">
                    <option value="📚">📚 Sách</option>
                    <option value="💻">💻 Máy tính</option>
                    <option value="🎨">🎨 Thiết kế</option>
                    <option value="⚡">⚡ Công nghệ</option>
                    <option value="🐍">🐍 Python</option>
                    <option value="🤖">🤖 AI</option>
                </select>
            </div>

            <div class="form-group">
                <label for="field-color">Màu hiển thị</label>
<select id="field-color" name="color">
                    <option value="blue">Xanh dương</option>
                    <option value="purple">Tím</option>
                    <option value="yellow">Vàng</option>
                    <option value="green">Xanh lá</option>
                    <option value="orange">Cam</option>
                    <option value="pink">Hồng</option>
                </select>
            </div>

            <button type="submit"
                    class="btn btn-primary auth-submit" <?= !$classesReady ? "disabled" : "" ?>>
                Tạo lớp học
            </button>

        </form>

        <p class="auth-back">
            <a href="courses.php">
                ← Danh sách lớp học của tôi
            </a>
        </p>

    </div>
<?php eduEnd(); ?>
