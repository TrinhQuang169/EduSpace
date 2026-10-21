
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

<?php require_once __DIR__ . "/../includes/dashboard.php"; eduDashboard($pdo); ?>
