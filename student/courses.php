
<?php
require_once __DIR__ . "/../includes/auth.php";

requireRole("student");
require_once __DIR__ . '/../includes/classes.php';
$classesReady = eduPrepareClasses($pdo);

$studentId = (int)$_SESSION["user_id"];

// Tạo CSRF token cho biểu mẫu đăng ký
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Chỉ lấy lớp học đã được Admin duyệt
// Đồng thời kiểm tra học sinh đã đăng ký chưa
$stmt = $pdo->prepare(
    "SELECT
        c.id,
        c.title,
        c.description,
        c.category,
        c.teacher_name,
        c.status,
        EXISTS (
            SELECT 1
            FROM enrollments e
            WHERE e.course_id = c.id
              AND e.student_id = ?
        ) AS is_enrolled
     FROM courses c
     WHERE c.status = 'approved'
     ORDER BY c.id DESC"
);

$stmt->execute([$studentId]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$joinCourseId = filter_input(INPUT_GET, 'join', FILTER_VALIDATE_INT);
$joinCourse = null;
foreach ($courses as $item) {
    if ((int)$item['id'] === $joinCourseId && !$item['is_enrolled']) { $joinCourse = $item; break; }
}
$message = "";

$messages = [
    "enrolled" => "Tham gia lớp học thành công!",
    "already" => "Bạn đã tham gia lớp học này rồi."
];

$messageKey = $_GET["message"] ?? "";
$message = $messages[$messageKey] ?? "";

function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<?php require_once __DIR__ . "/../includes/ui.php"; eduStart('Khám phá lớp học', 'courses'); ?>



    <h1>Danh sách lớp học</h1><?php if (!$classesReady): ?><div class="alert alert-error"><?= e(eduClassMigrationMessage()) ?></div><?php endif; ?>

    <p>
        Khám phá kiến thức mới từ các lớp học đã được phê duyệt.
    </p>

    <?php if ($message !== ""): ?>
        <div class="alert alert-success">
            <?php echo e($message); ?>
        </div>
    <?php endif; ?>

    <div class="edu-course-grid" id="course-list">
<?php foreach ($courses as $course): ?>
    <article class="edu-course-card">
        <div class="edu-course-cover"><?= eduIcon('book') ?><span>EDUSPACE / LEARNING</span></div>
        <div class="edu-course-body">
            <div class="edu-course-meta"><span class="edu-course-category"><?= e($course['category']) ?></span><?= eduBadge($course['is_enrolled'] ? 'enrolled' : 'available') ?></div>
            <h2><?= e($course['title']) ?></h2>
            <p class="edu-course-description"><?= e($course['description'] ?? '') ?></p>
            <p class="edu-course-teacher"><?= eduIcon('users') ?><?= e($course['teacher_name'] ?? 'Chưa xác định') ?></p>
            <div class="edu-course-actions"><?php if ($course['is_enrolled']): ?>
    <a class="btn btn-primary" href="course.php?course_id=<?= (int)$course['id'] ?>">Vào lớp học <?= eduIcon('arrow') ?></a>
<?php else: ?>
    <?php if ($classesReady): ?>
    <a class="btn btn-primary edu-join-trigger" href="courses.php?join=<?= (int)$course['id'] ?>#class-join-panel" data-course-id="<?= (int)$course['id'] ?>" data-class-title="<?= e($course['title']) ?>">Tham gia lớp học <?= eduIcon('arrow') ?></a>
    <?php else: ?><span class="edu-quiz-hint">Tạm thời chưa thể tham gia</span><?php endif; ?>
<?php endif; ?></div>
        </div>
    </article>
<?php endforeach; ?>
<?php if (empty($courses)): ?>
    <div class="edu-empty"><?= eduIcon('book') ?><h2>Lớp học đang được chuẩn bị</h2><p>Các lớp học sẽ xuất hiện tại đây sau khi được phê duyệt.</p></div>
<?php endif; ?>
</div>

    <p style="margin-top: 25px;">
        <a href="../dashboards/student.php">
            ← Quay lại Dashboard học sinh
        </a>
    </p>

<?php if ($classesReady): ?>
<section class="edu-panel edu-join-panel" id="class-join-panel" <?= $joinCourse ? '' : 'hidden' ?>>
    <button type="button" class="edu-join-close btn btn-secondary" aria-label="Đóng cửa sổ nhập mã" hidden>×</button>
    <span class="edu-stat-icon edu-tone-purple"><?= eduIcon('users') ?></span>
    <h2 id="class-join-title">Tham gia lớp học</h2>
    <p id="class-join-name"><?= e($joinCourse['title'] ?? '') ?></p>
    <p class="edu-quiz-hint" id="class-code-hint">Nhập mã gồm 16 ký tự do giáo viên cung cấp. Bạn có tối đa 5 lần nhập sai trong 15 phút.</p>
    <form action="enroll.php" method="POST" id="class-join-form">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="course_id" value="<?= (int)($joinCourse['id'] ?? 0) ?>">
        <div class="form-group"><label for="class-code">Mã tham gia lớp</label><input type="text" id="class-code" name="class_code" maxlength="16" minlength="16" pattern="[A-Fa-f0-9]{16}" autocomplete="off" spellcheck="false" autocapitalize="characters" aria-describedby="class-code-hint class-join-error" placeholder="Nhập mã lớp của bạn" required></div>
        <div class="alert alert-error" id="class-join-error" role="alert" hidden></div>
        <button type="submit" class="btn btn-primary">Xác nhận tham gia <?= eduIcon('arrow') ?></button>
    </form>
</section>
<script src="../assets/js/class-join.js" defer></script>
<?php endif; ?>
<?php eduEnd(); ?>
