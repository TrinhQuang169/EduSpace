
<?php
require_once __DIR__ . "/../includes/auth.php";
requireRole("admin");


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
    <link rel="stylesheet" href="../assets/css/style.css">
    <meta charset="UTF-8">
    <title>Dashboard quản trị</title>
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
    <h1>🛡️ Dashboard Admin</h1>
    <p>Xin chào, <?php echo $name; ?>!</p>
    <p>Bạn đã đăng nhập với quyền quản trị viên.</p>
    <a href="../index.php">Về trang chủ</a>
    
<p style="margin-top: 20px;">
    <a href="../admin/courses.php"
       class="btn btn-primary">
        📚 Quản lý và phê duyệt khóa học
    </a>
</p>

</body>
</html>
