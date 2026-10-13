
<?php
require_once __DIR__ . "/../config/database.php";

$errors = [];
$success = "";
$full_name = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    // 1. Kiểm tra họ tên
    if ($full_name === "") {
        $errors[] = "Vui lòng nhập họ và tên.";
    } elseif (mb_strlen($full_name, "UTF-8") > 100) {
        $errors[] = "Họ tên không được vượt quá 100 ký tự.";
    }

    // 2. Kiểm tra email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)
        || strlen($email) > 150) {
        $errors[] = "Email không hợp lệ.";
    }

    // 3. Kiểm tra mật khẩu
    if (strlen($password) < 8) {
        $errors[] = "Mật khẩu phải có ít nhất 8 ký tự.";
    }

    if (strlen($password) > 72) {
        $errors[] = "Mật khẩu không được vượt quá 72 byte.";
    }

    // 4. Kiểm tra nhập lại mật khẩu
    if ($password !== $confirm_password) {
        $errors[] = "Mật khẩu nhập lại không khớp.";
    }

    // 5. Kiểm tra email đã tồn tại
    if (empty($errors)) {
        $sql = "SELECT id FROM users WHERE email = ? LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $errors[] = "Email này đã được đăng ký.";
        }
    }

    // 6. Lưu tài khoản mới
    if (empty($errors)) {
        $password_hash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        try {
            $sql = "INSERT INTO users
                    (full_name, email, password_hash, role, status)
                    VALUES (?, ?, ?, 'student', 'active')";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $full_name,
                $email,
                $password_hash
            ]);

            $success = "Đăng ký tài khoản thành công!";
            $full_name = "";
            $email = "";

        } catch (PDOException $e) {
            // UNIQUE trong MySQL vẫn ngăn email trùng
            // khi hai yêu cầu đăng ký đến cùng lúc.
            if ($e->getCode() === "23000") {
                $errors[] = "Email này đã được đăng ký.";
            } else {
                error_log($e->getMessage());
                $errors[] = "Có lỗi xảy ra. Vui lòng thử lại.";
            }
        }
    }
}

function e($value) {
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
    <title>Đăng ký - EduSpace</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page">

    <div class="auth-container">

        <div class="auth-card">

            <a href="../index.php" class="auth-logo">
                🎓 EduSpace
            </a>

            <h1>Tạo tài khoản</h1>
            <p class="auth-subtitle">
                Bắt đầu hành trình học tập cùng EduSpace
            </p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo e($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success !== ""): ?>
                <div class="alert alert-success">
                    <?php echo e($success); ?>
                    <p>
                        Bạn có thể đăng nhập ở bước tiếp theo.
                    </p>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php">

                <div class="form-group">
                    <label for="full_name">Họ và tên</label>
                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        placeholder="Nhập họ và tên"
                        maxlength="100"
                        value="<?php echo e($full_name); ?>"
                        autocomplete="name"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Nhập địa chỉ email"
                        maxlength="150"
                        value="<?php echo e($email); ?>"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Ít nhất 8 ký tự"
                        minlength="8"
                        maxlength="72"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="confirm_password">
                        Nhập lại mật khẩu
                    </label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Nhập lại mật khẩu"
                        minlength="8"
                        maxlength="72"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <button type="submit"
                        class="btn btn-primary auth-submit">
                    Đăng ký tài khoản
                </button>

            </form>

            <p class="auth-bottom">
                Đã có tài khoản?
                <a href="login.php">Đăng nhập</a>
            </p>

            <p class="auth-back">
                <a href="../index.php">← Về trang chủ</a>
            </p>

        </div>
    </div>

</body>
</html>
