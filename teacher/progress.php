
<?php
require_once __DIR__ . "/../includes/auth.php";
requireRole("teacher");

$teacherId = (int)$_SESSION["user_id"];

function e($v) {
    return htmlspecialchars(
        (string)$v, ENT_QUOTES, "UTF-8"
    );
}

$stmt = $pdo->prepare(
    "SELECT id, title, status
     FROM courses
     WHERE teacher_id = ?
     ORDER BY id DESC"
);
$stmt->execute([$teacherId]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$courseId = filter_input(
    INPUT_GET, "course_id", FILTER_VALIDATE_INT
);

$selectedCourse = null;
$students = [];

if ($courseId) {
    $stmt = $pdo->prepare(
        "SELECT id, title FROM courses
         WHERE id = ? AND teacher_id = ?"
    );
    $stmt->execute([$courseId, $teacherId]);
    $selectedCourse = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$selectedCourse) {
        http_response_code(404);
        exit("Không tìm thấy lớp học của bạn.");
    }

    $stmt = $pdo->prepare(
        "SELECT
            e.student_id,
            e.enrolled_at,

            (SELECT COUNT(*)
             FROM lessons l
             WHERE l.course_id = e.course_id
            ) AS total_lessons,

            (SELECT COUNT(*)
             FROM lesson_progress p
             JOIN lessons l ON l.id = p.lesson_id
             WHERE l.course_id = e.course_id
               AND p.student_id = e.student_id
            ) AS completed_lessons,

            (SELECT COUNT(*)
             FROM quiz_attempts a
             JOIN quizzes q ON q.id = a.quiz_id
             JOIN lessons l ON l.id = q.lesson_id
             WHERE l.course_id = e.course_id
               AND a.student_id = e.student_id
            ) AS completed_quizzes,

            (SELECT ROUND(AVG(a.score), 2)
             FROM quiz_attempts a
             JOIN quizzes q ON q.id = a.quiz_id
             JOIN lessons l ON l.id = q.lesson_id
             WHERE l.course_id = e.course_id
               AND a.student_id = e.student_id
            ) AS average_score

         FROM enrollments e
         WHERE e.course_id = ?
         ORDER BY e.enrolled_at DESC, e.id DESC"
    );

    $stmt->execute([$courseId]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<?php require_once __DIR__ . "/../includes/ui.php"; eduStart('Tiến độ & kết quả', 'progress'); ?>


<h1>Theo dõi tiến độ học sinh</h1>

<p>Chọn lớp học để theo dõi mức độ hoàn thành và kết quả trắc nghiệm của học sinh.</p><h2>Lớp học của tôi</h2>

<div class="edu-table-wrap" role="region" aria-label="Bảng dữ liệu" tabindex="0"><table class="data-table">
<thead>
<tr>
<th>ID</th>
<th>Tên lớp học</th>
<th>Trạng thái</th>
<th>Thao tác</th>
</tr>
</thead>
<tbody>
<?php foreach ($courses as $course): ?>
<tr>
<td><?= (int)$course["id"] ?></td>
<td><?= e($course["title"]) ?></td>
<td><?= eduBadge($course["status"]) ?></td>
<td>
<a href="progress.php?course_id=<?= (int)$course["id"] ?>">
 Xem tiến độ
</a>
</td>
</tr>
<?php endforeach; ?><?php if (!$courses): ?><tr><td colspan="4">Bạn chưa có lớp học nào. Hãy tạo lớp học để bắt đầu.</td></tr><?php endif; ?></tbody>
</table></div>

<?php if ($selectedCourse): ?>
<hr>
<h2>Học sinh lớp: <?= e($selectedCourse["title"]) ?></h2>
<p>Số học sinh: <?= count($students) ?></p>

<div class="edu-table-wrap" role="region" aria-label="Bảng dữ liệu" tabindex="0"><table class="data-table">
<thead>
<tr>
<th>ID học sinh</th>
<th>Ngày tham gia</th>
<th>Hoàn thành</th>
<th>Tiến độ</th>
<th>Số trắc nghiệm đã làm</th>
<th>Điểm trung bình</th>
</tr>
</thead>
<tbody>
<?php foreach ($students as $student): ?>
<?php
$total = (int)$student["total_lessons"];
$completed = (int)$student["completed_lessons"];
$percent = $total > 0
    ? round($completed * 100 / $total, 1)
    : 0;
?>
<tr>
<td><?= (int)$student["student_id"] ?></td>
<td><?= e($student["enrolled_at"]) ?></td>
<td><?= $completed ?>/<?= $total ?></td>
<td class="edu-progress-cell"><?= $percent ?>%<progress class="edu-progress" value="<?= $percent ?>" max="100" aria-label="Tiến độ học sinh"><?= $percent ?>%</progress></td>
<td><?= (int)$student["completed_quizzes"] ?></td>
<td>
<?= $student["average_score"] === null
    ? "Chưa có"
    : e($student["average_score"]) . "/10" ?>
</td>
</tr>
<?php endforeach; ?>

<?php if (!$students): ?>
<tr><td colspan="6">Chưa có học sinh đăng ký.</td></tr>
<?php endif; ?>
</tbody>
</table></div>
<?php endif; ?>

<p style="margin-top:25px">
<a href="../dashboards/teacher.php">
 ← Quay lại Dashboard giáo viên
</a>
</p>
<?php eduEnd(); ?>
