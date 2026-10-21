<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/classes.php';
requireRole('student');
$wantsJson = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;

function enrollmentResponse(int $status, string $message, int $courseId = 0, bool $success = false): void {
    global $wantsJson;
    if ($wantsJson) {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['ok' => $success, 'message' => $message,
            'redirect' => $success ? 'course.php?course_id=' . $courseId : null], JSON_UNESCAPED_UNICODE);
    } elseif ($success) {
        header('Location: course.php?course_id=' . $courseId, true, 303);
    } else {
        http_response_code($status);
        require_once __DIR__ . '/../includes/ui.php';
        eduStart('Tham gia lớp học', 'courses');
        echo '<h1>Tham gia lớp học</h1><div class="alert alert-error" role="alert">' . eduEscape($message) . '</div>';
        echo '<a class="btn btn-primary" href="courses.php' . ($courseId > 0 ? '?join=' . $courseId . '#class-join-panel' : '') . '">Quay lại danh sách lớp</a>';
        eduEnd();
    }
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    enrollmentResponse(405, 'Chỉ chấp nhận yêu cầu tham gia bằng POST.');
}
$token = $_POST['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
    enrollmentResponse(403, 'Phiên xác nhận không hợp lệ. Hãy tải lại trang và thử lại.');
}
$courseId = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
if (!$courseId || $courseId < 1) enrollmentResponse(400, 'ID lớp học không hợp lệ.');
try {
    if (!eduClassSchemaReady($pdo)) enrollmentResponse(503, eduClassMigrationMessage(), $courseId);
    $result = eduJoinClass($pdo, (int)$_SESSION['user_id'], $courseId, $_POST['class_code'] ?? null);
    if (in_array($result['status'], ['enrolled', 'already'], true)) enrollmentResponse(200, 'Bạn đã tham gia lớp học.', $courseId, true);
    if ($result['status'] === 'throttled') {
        header('Retry-After: ' . $result['retry_after']);
        enrollmentResponse(429, 'Bạn đã nhập sai mã 5 lần trong 15 phút. Vui lòng thử lại sau ' . (int)ceil($result['retry_after'] / 60) . ' phút.', $courseId);
    }
    if ($result['status'] === 'unavailable') enrollmentResponse(403, 'Lớp học không tồn tại hoặc chưa được phê duyệt.');
    enrollmentResponse(400, 'Mã lớp không đúng hoặc bị bỏ trống. Hãy kiểm tra lại mã giáo viên cung cấp.', $courseId);
} catch (Throwable $error) {
    error_log('Class enrollment failed: ' . $error->getMessage());
    enrollmentResponse(500, 'Không thể tham gia lớp lúc này. Vui lòng thử lại.', $courseId);
}
