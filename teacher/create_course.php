
<?php
require_once __DIR__ . "/../includes/auth.php";
requireRole("teacher");

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

    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $icon = trim($_POST["icon"] ?? "📚");
    $color = $_POST["color"] ?? "blue";

    if ($title === "" || $category === "") {
        $error = "Vui lòng nhập tên và danh mục khóa học.";

    } elseif (mb_strlen($title) > 255) {
        $error = "Tên khóa học quá dài.";

    } elseif (mb_strlen($category) > 100) {
        $error = "Tên danh mục quá dài.";

    } elseif (mb_strlen($description) > 5000) {
        $error = "Mô tả không được vượt quá 5000 ký tự.";

    } elseif (!in_array($color, $allowedColors, true)) {
        $error = "Màu khóa học không hợp lệ.";

    } else {
        // Lấy ID giảng viên từ Session
        $teacherId = (int)$_SESSION["user_id"];
        $teacherName = $_SESSION["user_name"];

        $sql = "INSERT INTO courses
                (teacher_id, title, description,
                 category, icon, color,
                 teacher_name, students, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 0, 'draft')";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $teacherId,
            $title,
            $description,
            $category,
            $icon,
            $color,
            $teacherName
        ]);

        $success = "Tạo khóa học thành công!";

        $title = "";
        $description = "";
        $category = "";
        $icon = "📚";
        $color = "blue";
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
    <title>Tạo khóa học - EduSpace</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page">

<div class="auth-container">
    <div class="auth-card">

        <a href="../dashboards/teacher.php"
           class="auth-logo">
            🎓 EduSpace
        </a>

        <h1>Tạo khóa học mới</h1>

        <p class="auth-subtitle">
            Thêm khóa học vào hệ thống
        </p>

        <?php if ($error !== ""): ?>
            <div class="alert alert-error">
                <?php echo e($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success !== ""): ?>
            <div class="alert alert-success">
                <?php echo e($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <input type="hidden"
                   name="csrf_token"
                   value="<?php echo e(
                       $_SESSION["csrf_token"]
                   ); ?>">

            <div class="form-group">
                <label>Tên khóa học</label>
                <input type="text"
                       name="title"
                       maxlength="255"
                       value="<?php echo e($title); ?>"
                       placeholder="Ví dụ: PHP nâng cao"
                       required>
            </div>

            <div class="form-group">
                <label>Mô tả khóa học</label>
                <textarea name="description"
                          rows="5"
                          maxlength="5000"
                          placeholder="Giới thiệu khóa học..."><?php
                    echo e($description);
                ?></textarea>
            </div>

            <div class="form-group">
                <label>Danh mục</label>
                <input type="text"
                       name="category"
                       maxlength="100"
                       value="<?php echo e($category); ?>"
                       placeholder="Ví dụ: Lập trình"
                       required>
            </div>

            <div class="form-group">
                <label>Biểu tượng</label>
                <select name="icon">
                    <option value="📚">📚 Sách</option>
                    <option value="💻">💻 Máy tính</option>
                    <option value="🎨">🎨 Thiết kế</option>
                    <option value="⚡">⚡ Công nghệ</option>
                    <option value="🐍">🐍 Python</option>
                    <option value="🤖">🤖 AI</option>
                </select>
            </div>

            <div class="form-group">
                <label>Màu hiển thị</label>
                <select name="color">
                    <option value="blue">Xanh dương</option>
                    <option value="purple">Tím</option>
                    <option value="yellow">Vàng</option>
                    <option value="green">Xanh lá</option>
                    <option value="orange">Cam</option>
                    <option value="pink">Hồng</option>
                </select>
            </div>

            <button type="submit"
                    class="btn btn-primary auth-submit">
                Tạo khóa học
            </button>

        </form>

        <p class="auth-back">
            <a href="courses.php">
                ← Danh sách khóa học của tôi
            </a>
        </p>

    </div>
</div>

</body>
</html>
