
<?php
require_once __DIR__ . "/../includes/auth.php";
requireRole("student");

$studentId = (int)$_SESSION["user_id"];

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function e($v) {
    return htmlspecialchars(
        (string)$v, ENT_QUOTES, "UTF-8"
    );
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        http_response_code(403);
        exit("Yêu cầu không hợp lệ.");
    }

    $lessonId = filter_input(
        INPUT_POST, "lesson_id", FILTER_VALIDATE_INT
    );

    if (!$lessonId) {
        http_response_code(400);
        exit("ID bài học không hợp lệ.");
    }

    try {
        $pdo->beginTransaction();

        // Xác định đúng lớp học và kiểm tra quyền
        $stmt = $pdo->prepare(
            "SELECT c.id, c.status
             FROM lessons l
             JOIN courses c ON c.id = l.course_id
             JOIN enrollments e ON e.course_id = c.id
             WHERE l.id = ? AND e.student_id = ?
             FOR UPDATE"
        );

        $stmt->execute([$lessonId, $studentId]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$course || $course["status"] !== "approved") {
            $pdo->rollBack();
            http_response_code(403);
            exit("Bạn không có quyền hoàn thành bài học này.");
        }

        // Chống lưu trùng tiến độ
        $stmt = $pdo->prepare(
            "INSERT INTO lesson_progress
                (student_id, lesson_id)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE id = id"
        );

        $stmt->execute([$studentId, $lessonId]);
        $pdo->commit();

        header(
            "Location: progress.php?course_id=" .
            (int)$course["id"]
        );
        exit;

    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log($ex->getMessage());
        http_response_code(500);
        exit("Không thể cập nhật tiến độ.");
    }
}

$courseId = filter_input(
    INPUT_GET, "course_id", FILTER_VALIDATE_INT
);

if (!$courseId) {
    http_response_code(400);
    exit("ID lớp học không hợp lệ.");
}

// Chỉ xem tiến độ lớp học mình đã đăng ký
$stmt = $pdo->prepare(
    "SELECT c.id, c.title
     FROM courses c
     JOIN enrollments e ON e.course_id = c.id
     WHERE c.id = ?
       AND e.student_id = ?
       AND c.status = 'approved'"
);
$stmt->execute([$courseId, $studentId]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    http_response_code(403);
    exit("Bạn không có quyền xem tiến độ lớp học.");
}

// Lấy bài học, trạng thái hoàn thành và điểm quiz
$stmt = $pdo->prepare(
    "SELECT
        l.id,
        l.title,
        l.lesson_order,
        p.completed_at,
        q.id AS quiz_id,
        a.score
     FROM lessons l
     LEFT JOIN lesson_progress p
       ON p.lesson_id = l.id
       AND p.student_id = ?
     LEFT JOIN quizzes q
       ON q.lesson_id = l.id
     LEFT JOIN quiz_attempts a
       ON a.quiz_id = q.id
       AND a.student_id = ?
     WHERE l.course_id = ?
     ORDER BY l.lesson_order, l.id"
);
$stmt->execute([$studentId, $studentId, $courseId]);
$lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = count($lessons);
$completed = 0;
$quizDone = 0;
$scoreSum = 0;

foreach ($lessons as $lesson) {
    if ($lesson["completed_at"] !== null) {
        $completed++;
    }

    if ($lesson["score"] !== null) {
        $quizDone++;
        $scoreSum += (float)$lesson["score"];
    }
}

$percent = $total > 0
    ? round($completed * 100 / $total, 1)
    : 0;

$average = $quizDone > 0
    ? round($scoreSum / $quizDone, 2)
    : null;
?>
<?php require_once __DIR__ . "/../includes/ui.php"; eduStart('Tiến độ học tập', 'my-courses'); ?>


<h1>Tiến độ học tập</h1>
<h2><?= e($course["title"]) ?></h2>

<div class="edu-stats">
<?php eduStat($completed . '/' . $total, 'Bài học hoàn thành', 'check', 'green');
eduStat($percent . '%', 'Tiến độ lớp học', 'chart', 'blue');
eduStat($quizDone, 'Trắc nghiệm đã làm', 'file', 'purple');
eduStat($average === null ? '—' : $average . '/10', 'Điểm trung bình', 'award', 'orange'); ?>
</div>
<div class="edu-panel"><h2>Hành trình của bạn</h2><p>Đã hoàn thành <?= $completed ?> trong tổng số <?= $total ?> bài học.</p><progress class="edu-progress" value="<?= $percent ?>" max="100" aria-label="Tiến độ học tập"><?= $percent ?>%</progress></div><div class="edu-table-wrap" role="region" aria-label="Bảng dữ liệu" tabindex="0"><table class="data-table">
<thead>
<tr>
<th>Thứ tự</th>
<th>Bài học</th>
<th>Trạng thái</th>
<th>Điểm trắc nghiệm</th>
<th>Thao tác</th>
</tr>
</thead>
<tbody>
<?php foreach ($lessons as $lesson): ?>
<tr>
<td><?= (int)$lesson["lesson_order"] ?></td>
<td><?= e($lesson["title"]) ?></td>

<td>
<?= eduBadge($lesson["completed_at"] !== null ? "complete" : "incomplete") ?>
</td>

<td>
<?= $lesson["score"] !== null
    ? e($lesson["score"]) . "/10"
    : "Chưa làm" ?>
</td>

<td>
<a href="lesson.php?lesson_id=<?= (int)$lesson["id"] ?>">
 Xem bài học
</a>

<?php if ($lesson["completed_at"] === null): ?>
<form method="POST" style="display:inline-block">
<input type="hidden" name="csrf_token"
 value="<?= e($_SESSION["csrf_token"]) ?>">
<input type="hidden" name="lesson_id"
 value="<?= (int)$lesson["id"] ?>">
<button type="submit">Đánh dấu hoàn thành</button>
</form>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>

<?php if (!$lessons): ?>
<tr><td colspan="5">Chưa có bài học.</td></tr>
<?php endif; ?>
</tbody>
</table></div>

<p style="margin-top:25px">
<a href="course.php?course_id=<?= $courseId ?>">
 ← Quay lại lớp học
</a>
</p>

<?php eduEnd(); ?>
