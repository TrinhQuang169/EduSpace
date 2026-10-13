
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
    "students" => "Số học viên",
    "teachers" => "Số giảng viên",
    "courses" => "Tổng khóa học",
    "approved" => "Khóa học đã duyệt",
    "pending" => "Khóa học chờ duyệt",
    "lessons" => "Tổng bài học",
    "enrollments" => "Lượt đăng ký khóa học",
    "completed_lessons" => "Lượt hoàn thành bài học",
    "quiz_attempts" => "Lượt làm trắc nghiệm"
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Thống kê Admin - EduSpace</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container" style="padding:40px 0">

<h1>Thống kê hệ thống EduSpace</h1>

<table class="data-table">
<thead>
<tr>
<th>Chỉ số</th>
<th>Giá trị</th>
</tr>
</thead>
<tbody>
<?php foreach ($stats as $key => $value): ?>
<tr>
<td><?= htmlspecialchars($labels[$key], ENT_QUOTES, "UTF-8") ?></td>
<td><?= $value ?></td>
</tr>
<?php endforeach; ?>

<tr>
<td>Điểm trắc nghiệm trung bình toàn hệ thống</td>
<td>
<?= $averageScore === null
    ? "Chưa có"
    : htmlspecialchars((string)$averageScore, ENT_QUOTES, "UTF-8") . "/10" ?>
</td>
</tr>

</tbody>
</table>

<p style="margin-top:25px">
<a href="../dashboards/admin.php">
 ← Quay lại Dashboard Admin
</a>
</p>

</div>
</body>
</html>
