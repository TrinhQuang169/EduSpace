
<?php
require_once __DIR__ . "/../includes/auth.php";
requireRole("teacher");


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
    <title>Dashboard giảng viên</title>
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
    <h1>👨‍🏫 Dashboard giảng viên</h1>
    <p>Xin chào, <?php echo $name; ?>!</p>
    <p>Bạn đã đăng nhập với quyền giảng viên.</p>
    <a href="../index.php">Về trang chủ</a>
    
<p style="margin-top: 20px;">
    <a href="../teacher/courses.php"
       class="btn btn-primary">
        📚 Quản lý khóa học
    </a>
</p>

</body>
</html>
