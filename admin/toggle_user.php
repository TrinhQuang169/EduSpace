
<?php
require_once __DIR__ . "/../includes/auth.php";

requireRole("admin");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Phương thức không hợp lệ.");
}

$token = $_POST["csrf_token"] ?? "";

if (
    !isset($_SESSION["csrf_token"]) ||
    !is_string($token) ||
    !hash_equals($_SESSION["csrf_token"], $token)
) {
    http_response_code(403);
    exit("Yêu cầu không hợp lệ.");
}

$userId = filter_input(
    INPUT_POST,
    "user_id",
    FILTER_VALIDATE_INT
);

if (!$userId || $userId < 1) {
    http_response_code(400);
    exit("ID tài khoản không hợp lệ.");
}

// Không cho Admin tự khóa chính mình
if ($userId === (int)$_SESSION["user_id"]) {
    http_response_code(403);
    exit("Bạn không thể tự khóa tài khoản.");
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "SELECT id, role, status
         FROM users
         WHERE id = ?
         FOR UPDATE"
    );

    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $pdo->rollBack();
        http_response_code(404);
        exit("Không tìm thấy tài khoản.");
    }

    // Không cho thay đổi trạng thái tài khoản Admin
    if ($user["role"] === "admin") {
        $pdo->rollBack();
        http_response_code(403);
        exit("Không thể khóa hoặc mở khóa Admin.");
    }

    if (!in_array(
        $user["status"],
        ["active", "locked"],
        true
    )) {
        $pdo->rollBack();
        http_response_code(409);
        exit("Trạng thái tài khoản không hợp lệ.");
    }

    // Chuyển đổi trạng thái
    $newStatus = $user["status"] === "active"
        ? "locked"
        : "active";

    $stmt = $pdo->prepare(
        "UPDATE users
         SET status = ?
         WHERE id = ?
           AND role IN ('student', 'teacher')"
    );

    $stmt->execute([$newStatus, $userId]);

    $pdo->commit();

    header("Location: users.php?message=updated");
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    http_response_code(500);
    exit("Không thể cập nhật tài khoản.");
}
