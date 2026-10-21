
<?php
require_once __DIR__ . "/../includes/auth.php";
requireRole("admin");

function countRows(PDO $pdo, string $sql): int {
    return (int)$pdo->query($sql)->fetchColumn();
}

$stats = [
    "students" => countRows(
        $pdo, "SELECT COUNT(*) FROM users
               WHERE role = 'student'"
    ),
    "teachers" => countRows(
        $pdo, "SELECT COUNT(*) FROM users
               WHERE role = 'teacher'"
    ),
    "courses" => countRows(
        $pdo, "SELECT COUNT(*) FROM courses"
    ),
    "approved" => countRows(
        $pdo, "SELECT COUNT(*) FROM courses
               WHERE status = 'approved'"
    ),
    "pending" => countRows(
        $pdo, "SELECT COUNT(*) FROM courses
               WHERE status = 'pending'"
    ),
    "lessons" => countRows(
        $pdo, "SELECT COUNT(*) FROM lessons"
    ),
    "enrollments" => countRows(
        $pdo, "SELECT COUNT(*) FROM enrollments"
    ),
    "completed_lessons" => countRows(
        $pdo, "SELECT COUNT(*) FROM lesson_progress"
    ),
    "quiz_attempts" => countRows(
        $pdo, "SELECT COUNT(*) FROM quiz_attempts"
    )
];

$averageScore = $pdo->query(
    "SELECT ROUND(AVG(score), 2) FROM quiz_attempts"
)->fetchColumn();

$labels = [
    "students" => "Số học sinh",
    "teachers" => "Số giáo viên",
    "courses" => "Tổng lớp học",
    "approved" => "Lớp học đã duyệt",
    "pending" => "Lớp học chờ duyệt",
    "lessons" => "Tổng bài học",
    "enrollments" => "Lượt tham gia lớp học",
    "completed_lessons" => "Lượt hoàn thành bài học",
    "quiz_attempts" => "Lượt làm trắc nghiệm"
];
?>
<?php require_once __DIR__ . "/../includes/ui.php"; eduStart('Thống kê hệ thống', 'stats'); ?>


<h1>Thống kê hệ thống EduSpace</h1>

<p>Bức tranh tổng quan về cộng đồng, nội dung và hoạt động học tập trên EduSpace.</p>
<div class="edu-stats">
<?php foreach ($stats as $key => $value): ?>
<?php eduStat($value, $labels[$key], in_array($key, ['students', 'teachers', 'enrollments']) ? 'users' : (in_array($key, ['pending', 'quiz_attempts']) ? 'clock' : 'book'), in_array($key, ['approved', 'completed_lessons']) ? 'green' : ($key === 'pending' ? 'orange' : 'purple')); ?>
<?php endforeach; ?>
<?php eduStat($averageScore === null ? '—' : $averageScore . '/10', 'Điểm trắc nghiệm trung bình', 'award', 'blue'); ?>
</div>
<section class="edu-panel"><h2>Tình hình phê duyệt lớp học</h2>
<p>Đã duyệt <strong><?= $stats['approved'] ?> / <?= $stats['courses'] ?></strong> lớp học.</p>
<progress class="edu-progress" value="<?= $stats['approved'] ?>" max="<?= max(1, $stats['courses']) ?>" aria-label="Tỷ lệ lớp học đã duyệt"></progress>
<p><?= $stats['pending'] ?> lớp học đang chờ được xem xét.</p>
<a class="btn btn-secondary" href="courses.php">Quản lý lớp học <?= eduIcon('arrow') ?></a></section>

<p style="margin-top:25px">
<a href="../dashboards/admin.php">
 ← Quay lại Dashboard Admin
</a>
</p>

<?php eduEnd(); ?>
