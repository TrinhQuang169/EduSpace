
<?php
require_once __DIR__ . "/../config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    ini_set("session.use_strict_mode", "1");
    ini_set("session.use_only_cookies", "1");
    ini_set("session.cookie_httponly", "1");
    ini_set("session.cookie_samesite", "Lax");

    if (!empty($_SERVER["HTTPS"]) &&
        $_SERVER["HTTPS"] !== "off") {
        ini_set("session.cookie_secure", "1");
    }

    session_start();
}

function isLoggedIn() {
    return isset(
        $_SESSION["user_id"],
        $_SESSION["user_role"]
    );
}

function clearLoginSession() {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            "",
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /online-learning/auth/login.php");
        exit;
    }

    global $pdo;

    $stmt = $pdo->prepare(
        "SELECT id, full_name, email, role, status
         FROM users
         WHERE id = ?
         LIMIT 1"
    );

    $stmt->execute([$_SESSION["user_id"]]);
    $user = $stmt->fetch();

    if (!$user || $user["status"] !== "active") {
        clearLoginSession();

        header("Location: /online-learning/auth/login.php");
        exit;
    }

    $_SESSION["user_name"] = $user["full_name"];
    $_SESSION["user_email"] = $user["email"];
    $_SESSION["user_role"] = $user["role"];
}

function requireRole($role) {
    requireLogin();

    if ($_SESSION["user_role"] !== $role) {
        http_response_code(403);
        exit("Bạn không có quyền truy cập trang này.");
    }
}

function redirectByRole() {
    $role = $_SESSION["user_role"] ?? "";

    if ($role === "admin") {
        header("Location: /online-learning/dashboards/admin.php");
    } elseif ($role === "teacher") {
        header("Location: /online-learning/dashboards/teacher.php");
    } elseif ($role === "student") {
        header("Location: /online-learning/dashboards/student.php");
    } else {
        header("Location: /online-learning/auth/login.php");
    }

    exit;
}
