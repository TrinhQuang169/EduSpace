
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
        exit("Không tìm thấy khóa học của bạn.");
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
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Tiến độ học viên - EduSpace</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container" style="padding:40px 0">

<h1>Theo dõi tiến độ học viên</h1>

<h2>Khóa học của tôi</h2>

<table class="data-table">
<thead>
<tr>
<th>ID</th>
<th>Tên khóa học</th>
<th>Trạng thái</th>
<th>Thao tác</th>
</tr>
</thead>
<tbody>
<?php foreach ($courses as $course): ?>
<tr>
<td><?= (int)$course["id"] ?></td>
<td><?= e($course["title"]) ?></td>
<td><?= e($course["status"]) ?></td>
<td>
<a href="progress.php?course_id=<?= (int)$course["id"] ?>">
 Xem tiến độ
</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php if ($selectedCourse): ?>
<hr>
<h2>Học viên khóa: <?= e($selectedCourse["title"]) ?></h2>
<p>Số học viên: <?= count($students) ?></p>

<table class="data-table">
<thead>
<tr>
<th>ID học viên</th>
<th>Ngày đăng ký</th>
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
<td><?= $percent ?>%</td>
<td><?= (int)$student["completed_quizzes"] ?></td>
<td>
<?= $student["average_score"] === null
    ? "Chưa có"
    : e($student["average_score"]) . "/10" ?>
</td>
</tr>
<?php endforeach; ?>

<?php if (!$students): ?>
<tr><td colspan="6">Chưa có học viên đăng ký.</td></tr>
<?php endif; ?>
</tbody>
</table>
<?php endif; ?>

<p style="margin-top:25px">
<a href="../dashboards/teacher.php">
 ← Quay lại Dashboard giảng viên
</a>
</p>
</div>
</body>
</html>
