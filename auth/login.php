
<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

// Nếu đã đăng nhập, chuyển thẳng đến Dashboard
if (isLoggedIn()) {
    requireLogin();

    if (isLoggedIn()) {
        redirectByRole();
    }
}

$error = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $error = "Vui lòng nhập email và mật khẩu.";
    } else {

        // Tìm tài khoản trong MySQL
        $sql = "SELECT id, full_name, email,
                       password_hash, role, status
                FROM users
                WHERE email = ?
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Kiểm tra email và mật khẩu
        if (
            !$user ||
            !password_verify(
                $password,
                $user["password_hash"]
            )
        ) {
            $error = "Email hoặc mật khẩu không chính xác.";

        } elseif ($user["status"] !== "active") {
            $error = "Tài khoản đã bị khóa.";

        } else {
            // Đổi Session ID sau khi đăng nhập
            session_regenerate_id(true);

            // Lưu thông tin đăng nhập
            $_SESSION["user_id"] = (int)$user["id"];
            $_SESSION["user_name"] = $user["full_name"];
            $_SESSION["user_email"] = $user["email"];
            $_SESSION["user_role"] = $user["role"];

            // Chuyển hướng theo vai trò
            redirectByRole();
        }
    }
}

function escapeHtml($value) {
    return htmlspecialchars(
        $value,
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
    <title>Đăng nhập - EduSpace</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">

            <a href="../index.php" class="auth-logo">
                🎓 EduSpace
            </a>

            <h1>Đăng nhập</h1>
            <p class="auth-subtitle">
                Chào mừng bạn quay trở lại EduSpace
            </p>

            <?php if ($error !== ""): ?>
                <div class="alert alert-error">
                    <?php echo escapeHtml($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">

                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Nhập email"
                        value="<?php echo escapeHtml($email); ?>"
                        autocomplete="username"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Nhập mật khẩu"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <button
                    type="submit"
                    class="btn btn-primary auth-submit">
                    Đăng nhập
                </button>

            </form>

            <p class="auth-bottom">
                Chưa có tài khoản?
                <a href="register.php">Đăng ký ngay</a>
            </p>

            <p class="auth-back">
                <a href="../index.php">← Về trang chủ</a>
            </p>

        </div>
    </div>
</body>
</html>
