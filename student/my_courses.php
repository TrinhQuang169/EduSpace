
<?php
require_once __DIR__ . "/../includes/auth.php";

// Chỉ học sinh được truy cập
requireRole("student");

// Lấy ID học sinh từ Session
$studentId = (int)$_SESSION["user_id"];

// Lấy các lớp học mà học sinh đã đăng ký
$stmt = $pdo->prepare(
    "SELECT
        c.id,
        c.title,
        c.description,
        c.category,
        c.teacher_name,
        c.status,
        e.enrolled_at
     FROM enrollments e
     JOIN courses c ON c.id = e.course_id
     WHERE e.student_id = ?
       AND c.status = 'approved'
     ORDER BY e.enrolled_at DESC, e.id DESC"
);

$stmt->execute([$studentId]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Đếm số lớp học
$totalCourses = count($courses);

// Hàm hiển thị dữ liệu an toàn
function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<?php require_once __DIR__ . "/../includes/ui.php"; eduStart('Lớp học của tôi', 'my-courses'); ?>



    <h1>Lớp học của tôi</h1>

    <p>
        Lớp học đã tham gia và hiện được phê duyệt:
        <strong><?php echo $totalCourses; ?></strong>
    </p>

    <div class="edu-course-grid" id="course-list">
<?php foreach ($courses as $course): ?>
    <article class="edu-course-card">
        <div class="edu-course-cover"><?= eduIcon('book') ?><span>EDUSPACE / LEARNING</span></div>
        <div class="edu-course-body">
            <div class="edu-course-meta"><span class="edu-course-category"><?= e($course['category']) ?></span><?= eduBadge('enrolled') ?></div>
            <h2><?= e($course['title']) ?></h2>
            <p class="edu-course-description"><?= e($course['description'] ?? '') ?></p>
            <p class="edu-course-teacher"><?= eduIcon('users') ?><?= e($course['teacher_name'] ?? 'Chưa xác định') ?></p>
            <div class="edu-course-actions"><a class="btn btn-primary" href="course.php?course_id=<?= (int)$course['id'] ?>">Vào lớp học <?= eduIcon('arrow') ?></a><a href="progress.php?course_id=<?= (int)$course['id'] ?>">Xem tiến độ</a><small>Ngày tham gia: <?= e($course['enrolled_at']) ?></small></div>
        </div>
    </article>
<?php endforeach; ?>
<?php if (empty($courses)): ?>
    <div class="edu-empty"><?= eduIcon('book') ?><h2>Bạn chưa có lớp học nào</h2><p>Khám phá các lớp học và chọn nội dung bạn muốn học.</p><a class="btn btn-primary" href="courses.php">Khám phá lớp học</a></div>
<?php endif; ?>
</div>

    <p style="margin-top: 25px;">
        <a href="courses.php">
            Xem thêm lớp học
        </a>
    </p>

    <p style="margin-top: 15px;">
        <a href="../dashboards/student.php">
            ← Quay lại Dashboard học sinh
        </a>
    </p>

<?php eduEnd(); ?>
