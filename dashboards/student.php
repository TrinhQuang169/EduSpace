
<?php
require_once __DIR__ . "/../includes/auth.php";

requireRole("student");

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}


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
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Dashboard học viên - EduSpace</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<form action="../auth/logout.php"
      method="POST"
      style="margin-top: 20px;">

    <input
        type="hidden"
        name="csrf_token"
        value="<?php echo htmlspecialchars(
            $_SESSION["csrf_token"],
            ENT_QUOTES,
            "UTF-8"
        ); ?>"
    >

    <button type="submit"
            class="btn btn-primary">
        Đăng xuất
    </button>
</form>


<body>

    <div class="container" style="padding: 60px 0;">
        <h1>🎓 Dashboard học viên</h1>

        <p>Xin chào, <?php echo $name; ?>!</p>

        <p>
            Bạn đã đăng nhập thành công
            với quyền học viên.
        </p>

        <p style="margin-top: 25px;">
            <a href="../index.php"
               class="btn btn-primary">
                Về trang chủ
            </a>
        </p>
    </div>

    
<div style="margin: 25px 0;">

    <h2>Chức năng học viên</h2>

    <p>
        <a
            href="../student/courses.php"
            class="btn btn-primary"
        >
            Danh sách khóa học
        </a>
    </p>

    <p style="margin-top: 15px;">
        <a
            href="../student/my_courses.php"
            class="btn btn-primary"
        >
            Khóa học của tôi
        </a>
    </p>

</div>


</body>
</html>
