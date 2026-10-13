
<?php
require_once __DIR__ . "/../includes/auth.php";
requireRole("teacher");

$courseId = filter_input(
    INPUT_GET,
    "course_id",
    FILTER_VALIDATE_INT
);

if (!$courseId || $courseId < 1) {
    http_response_code(400);
    exit("ID khóa học không hợp lệ.");
}

$teacherId = (int)$_SESSION["user_id"];

// Xác minh quyền sở hữu khóa học
$stmt = $pdo->prepare(
    "SELECT id, title, status
     FROM courses
     WHERE id = ? AND teacher_id = ?
     LIMIT 1"
);

$stmt->execute([$courseId, $teacherId]);
$course = $stmt->fetch();

if (!$course) {
    http_response_code(404);
    exit("Không tìm thấy khóa học của bạn.");
}

// Lấy bài học theo thứ tự
$stmt = $pdo->prepare(
    "SELECT id, title, lesson_order, video_url
     FROM lessons
     WHERE course_id = ?
     ORDER BY lesson_order ASC, id ASC"
);

$stmt->execute([$courseId]);
$lessons = $stmt->fetchAll();

$canEdit = in_array(
    $course["status"],
    ["draft", "rejected"],
    true
);

$message = (
    ($_GET["message"] ?? "") === "created"
) ? "Thêm bài học thành công!" : "";

function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Danh sách bài học - EduSpace</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container" style="padding: 50px 0;">

    <h1>📚 Bài học của khóa học</h1>

    <h2><?php echo e($course["title"]); ?></h2>

    <?php if ($message !== ""): ?>
        <div class="alert alert-success">
            <?php echo e($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($canEdit): ?>
        <p style="margin: 25px 0;">
            <a
                href="create_lesson.php?course_id=<?php echo $courseId; ?>"
                class="btn btn-primary"
            >
                + Thêm bài học
            </a>
        </p>
    <?php else: ?>
        <p>Khóa học đang chờ duyệt hoặc đã duyệt. Không thể thêm bài học.</p>
    <?php endif; ?>

    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Thứ tự</th>
                    <th>Tên bài học</th>
                    <th>Video</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($lessons as $lesson): ?>
                    <tr>
                        <td><?php echo (int)$lesson["lesson_order"]; ?></td>

                        <td><?php echo e($lesson["title"]); ?></td>

                        <td>
                            <?php if (!empty($lesson["video_url"])): ?>
                                <a
                                    href="<?php echo e($lesson["video_url"]); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    Xem video
                                </a>
                            <?php else: ?>
                                Chưa có
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($lessons)): ?>
                    <tr>
                        <td colspan="3">
                            Khóa học chưa có bài học nào.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <p style="margin-top: 25px;">
        <a href="courses.php">
            ← Quay lại danh sách khóa học
        </a>
    </p>

</div>

</body>
</html>
