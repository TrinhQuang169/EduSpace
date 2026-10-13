
<?php
require_once __DIR__ . "/../includes/auth.php";

// Chỉ cho phép đăng xuất qua POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    header("Allow: POST");
    exit("Phương thức không được hỗ trợ.");
}

// Kiểm tra mã bảo vệ chống CSRF
$token = $_POST["csrf_token"] ?? "";

if (
    !isset($_SESSION["csrf_token"]) ||
    !is_string($token) ||
    !hash_equals($_SESSION["csrf_token"], $token)
) {
    http_response_code(403);
    exit("Yêu cầu đăng xuất không hợp lệ.");
}

// Hủy Session đăng nhập
clearLoginSession();

// Chuyển về trang đăng nhập
header("Location: /online-learning/auth/login.php");
exit;
