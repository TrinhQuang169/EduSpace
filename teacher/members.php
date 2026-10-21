<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/classes.php';
requireRole('teacher');
$teacherId = (int)$_SESSION['user_id'];
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$error = '';
$classesReady = eduPrepareClasses($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403); exit('Yêu cầu không hợp lệ.');
    }
    $courseId = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
    $studentId = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
    if (!$courseId || $courseId < 1 || !$studentId || $studentId < 1 || ($_POST['action'] ?? '') !== 'remove') {
        http_response_code(400); exit('Yêu cầu xóa thành viên không hợp lệ.');
    }
    try {
        $result = eduRemoveClassMember($pdo, $teacherId, $courseId, $studentId);
        if ($result === 'forbidden') { http_response_code(404); exit('Không tìm thấy lớp học của bạn.'); }
        header('Location: members.php?course_id=' . $courseId . '&message=' . $result, true, 303);
        exit;
    } catch (Throwable $exception) {
        error_log('Class member removal failed: ' . $exception->getMessage());
        http_response_code(500);
        $error = 'Không thể xóa thành viên lúc này. Vui lòng thử lại.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET, POST'); http_response_code(405); exit('Phương thức không hợp lệ.');
}
$query = $pdo->prepare('SELECT id, title, status FROM courses WHERE teacher_id = ? ORDER BY id DESC');
$query->execute([$teacherId]);
$courses = $query->fetchAll(PDO::FETCH_ASSOC);
$courseId = $_SERVER['REQUEST_METHOD'] === 'POST' ? $courseId : filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);
if (isset($_GET['course_id']) && (!$courseId || $courseId < 1)) { http_response_code(400); exit('ID lớp học không hợp lệ.'); }
$course = null;
$members = [];
if ($courseId) {
    $query = $pdo->prepare('SELECT id, title, status' . ($classesReady ? ', class_code' : '') . ' FROM courses WHERE id = ? AND teacher_id = ?');
    $query->execute([$courseId, $teacherId]);
    $course = $query->fetch(PDO::FETCH_ASSOC);
    if (!$course) { http_response_code(404); exit('Không tìm thấy lớp học của bạn.'); }
    $query = $pdo->prepare(
        'SELECT e.student_id, e.enrolled_at, u.full_name, u.email,
            (SELECT COUNT(*) FROM lessons l WHERE l.course_id = e.course_id) AS total_lessons,
            (SELECT COUNT(*) FROM lesson_progress p JOIN lessons l ON l.id = p.lesson_id
             WHERE l.course_id = e.course_id AND p.student_id = e.student_id) AS completed_lessons,
            (SELECT COUNT(*) FROM quiz_attempts a JOIN quizzes q ON q.id = a.quiz_id JOIN lessons l ON l.id = q.lesson_id
             WHERE l.course_id = e.course_id AND a.student_id = e.student_id) AS completed_quizzes,
            (SELECT ROUND(AVG(a.score), 2) FROM quiz_attempts a JOIN quizzes q ON q.id = a.quiz_id JOIN lessons l ON l.id = q.lesson_id
             WHERE l.course_id = e.course_id AND a.student_id = e.student_id) AS average_score
         FROM enrollments e JOIN users u ON u.id = e.student_id
         WHERE e.course_id = ? ORDER BY e.enrolled_at DESC, e.id DESC'
    );
    $query->execute([$courseId]);
    $members = $query->fetchAll(PDO::FETCH_ASSOC);
}
require_once __DIR__ . '/../includes/ui.php';
eduStart('Thành viên lớp học', 'members');
?>
<h1>Thành viên lớp học</h1>
<p>Quản lý học sinh, theo dõi tiến độ và kết quả trong các lớp bạn phụ trách.</p>
<?php if (!$classesReady): ?><div class="alert alert-error"><?= eduEscape(eduClassMigrationMessage()) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error" role="alert"><?= eduEscape($error) ?></div><?php endif; ?>
<?php if (($_GET['message'] ?? '') === 'removed'): ?><div class="alert alert-success" role="status">Đã xóa học sinh khỏi lớp. Quyền truy cập lớp đã được thu hồi.</div><?php endif; ?>
<?php if (($_GET['message'] ?? '') === 'absent'): ?><div class="alert alert-success" role="status">Học sinh này không còn trong lớp.</div><?php endif; ?>
<form method="GET" class="edu-filter">
    <label for="members-course">Chọn lớp học</label>
    <select id="members-course" name="course_id" required>
        <option value="">Chọn lớp của bạn</option>
        <?php foreach ($courses as $item): ?><option value="<?= (int)$item['id'] ?>" <?= (int)$courseId === (int)$item['id'] ? 'selected' : '' ?>><?= eduEscape($item['title']) ?></option><?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary">Xem thành viên</button>
</form>
<?php if ($course): ?>
<section class="edu-panel">
    <div class="edu-section-heading"><h2><?= eduEscape($course['title']) ?></h2><?= eduBadge($course['status']) ?></div>
    <?php if ($classesReady): ?><p>Mã tham gia lớp: <code class="edu-class-code"><?= eduEscape($course['class_code']) ?></code></p><p class="edu-quiz-hint">Chỉ chia sẻ mã này với học sinh bạn muốn mời vào lớp.</p><?php endif; ?>
    <p><strong><?= count($members) ?></strong> học sinh đang tham gia.</p>
    <a href="lessons.php?course_id=<?= (int)$courseId ?>">Quản lý bài học →</a>
</section>
<div class="edu-table-wrap" role="region" aria-label="Danh sách thành viên lớp" tabindex="0">
<table class="data-table"><thead><tr><th>Học sinh</th><th>Email</th><th>Ngày tham gia</th><th>Tiến độ</th><th>Trắc nghiệm</th><th>Thao tác</th></tr></thead><tbody>
<?php foreach ($members as $member): ?>
<?php $percent = (int)$member['total_lessons'] > 0 ? round((int)$member['completed_lessons'] * 100 / (int)$member['total_lessons'], 1) : 0; ?>
<tr>
    <td><strong><?= eduEscape($member['full_name']) ?></strong></td>
    <td><?= eduEscape($member['email']) ?></td>
    <td><?= eduEscape($member['enrolled_at']) ?></td>
    <td class="edu-progress-cell"><?= (int)$member['completed_lessons'] ?>/<?= (int)$member['total_lessons'] ?> bài · <?= $percent ?>%<progress class="edu-progress" value="<?= $percent ?>" max="100" aria-label="Tiến độ học tập"><?= $percent ?>%</progress></td>
    <td><?= (int)$member['completed_quizzes'] ?> bài đã làm<br>Điểm TB: <strong><?= $member['average_score'] === null ? 'Chưa có' : eduEscape($member['average_score']) . '/10' ?></strong></td>
    <td><form method="POST" action="members.php" onsubmit="return confirm('Xóa học sinh khỏi lớp? Học sinh sẽ mất quyền truy cập nội dung lớp. Lịch sử học tập vẫn được giữ lại.');">
        <input type="hidden" name="csrf_token" value="<?= eduEscape($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="course_id" value="<?= (int)$courseId ?>">
        <input type="hidden" name="student_id" value="<?= (int)$member['student_id'] ?>">
        <input type="hidden" name="action" value="remove">
        <button type="submit" class="btn btn-danger">Xóa khỏi lớp</button>
    </form></td>
</tr>
<?php endforeach; ?>
<?php if (!$members): ?><tr><td colspan="6">Lớp chưa có học sinh. Hãy chia sẻ mã lớp để mời học sinh tham gia.</td></tr><?php endif; ?>
</tbody></table></div>
<p class="edu-quiz-hint">Xóa thành viên chỉ thu hồi quyền truy cập, không xóa bài làm và tiến độ. Học sinh có thể tham gia lại nếu được cung cấp đúng mã lớp.</p>
<?php else: ?>
<div class="edu-empty"><?= eduIcon('users') ?><h2><?= $courses ? 'Chọn lớp để bắt đầu' : 'Bạn chưa có lớp học' ?></h2><p><?= $courses ? 'Danh sách thành viên và kết quả sẽ xuất hiện tại đây.' : 'Tạo lớp học để mời học sinh tham gia.' ?></p></div>
<?php endif; ?>
<?php eduEnd(); ?>
