
<?php
require_once __DIR__ . "/../includes/auth.php";

requireRole("teacher");

// Tạo CSRF token
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Lấy tên giảng viên
$name = htmlspecialchars(
    $_SESSION["user_name"] ?? "",
    ENT_QUOTES,
    "UTF-8"
);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <title>Dashboard giảng viên - EduSpace</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<div class="container" style="padding: 40px 0;">

    <h1>👨‍🏫 Dashboard giảng viên</h1>

    <p>
        Xin chào, <?php echo $name; ?>!
    </p>

    <p>
        Bạn đã đăng nhập với quyền giảng viên.
    </p>

    <h2 style="margin-top: 30px;">
        Chức năng giảng viên
    </h2>

    <p style="margin-top: 20px;">
        <a
            href="../teacher/courses.php"
            class="btn btn-primary"
        >
            📚 Quản lý khóa học
        </a>
    </p>

    <p style="margin-top: 20px;">
        <a
            href="../teacher/progress.php"
            class="btn btn-primary"
        >
            📊 Theo dõi tiến độ học viên
        </a>
    </p>

    <p style="margin-top: 25px;">
        <a href="../index.php">
            ← Về trang chủ
        </a>
    </p>

    <form
        action="../auth/logout.php"
        method="POST"
        style="margin-top: 25px;"
    >
        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars(
                $_SESSION["csrf_token"],
                ENT_QUOTES,
                "UTF-8"
            ); ?>"
        >

        <button
            type="submit"
            class="btn btn-primary"
        >
            Đăng xuất
        </button>
    </form>

</div>

</body>
</html>
