
<?php
require_once __DIR__ . "/../includes/auth.php";

requireRole("student");

// Lấy ID khóa học
$courseId = filter_input(
    INPUT_GET,
    "course_id",
    FILTER_VALIDATE_INT
);

if (!$courseId || $courseId < 1) {
    http_response_code(400);
    exit("ID khóa học không hợp lệ.");
}

$studentId = (int)$_SESSION["user_id"];

// Kiểm tra học viên đã đăng ký và khóa học được duyệt
$stmt = $pdo->prepare(
    "SELECT
        c.id,
        c.title,
        c.description,
        c.category,
        c.teacher_name
     FROM courses c
     INNER JOIN enrollments e
         ON e.course_id = c.id
     WHERE c.id = ?
       AND e.student_id = ?
       AND c.status = 'approved'
     LIMIT 1"
);

$stmt->execute([$courseId, $studentId]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    http_response_code(403);
    exit(
        "Bạn chưa đăng ký khóa học này " .
        "hoặc khóa học chưa được phê duyệt."
    );
}

// Lấy danh sách bài học, trắc nghiệm và trạng thái làm bài
$stmt = $pdo->prepare(
    "SELECT
        l.id,
        l.title,
        l.lesson_order,
        l.video_url,
        q.id AS quiz_id,

        (
            SELECT COUNT(*)
            FROM quiz_questions qq
            WHERE qq.quiz_id = q.id
        ) AS total_questions,

        qa.id AS attempt_id,
        qa.score AS quiz_score

     FROM lessons l

     LEFT JOIN quizzes q
         ON q.lesson_id = l.id

     LEFT JOIN quiz_attempts qa
         ON qa.quiz_id = q.id
         AND qa.student_id = ?

     WHERE l.course_id = ?

     ORDER BY l.lesson_order ASC, l.id ASC"
);

$stmt->execute([$studentId, $courseId]);
$lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalLessons = count($lessons);

// Hiển thị dữ liệu HTML an toàn
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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Vào học - EduSpace</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >
</head>

<body>

<div class="container" style="padding: 40px 0;">

    <h1>Học trực tuyến</h1>

    <h2>
        <?php echo e($course["title"]); ?>
    </h2>

    <p>
        <strong>Giảng viên:</strong>
        <?php echo e(
            $course["teacher_name"] ?? "Chưa xác định"
        ); ?>
    </p>

    <p>
        <strong>Danh mục:</strong>
        <?php echo e($course["category"]); ?>
    </p>

    <p>
        <strong>Mô tả khóa học:</strong>
        <?php echo nl2br(e(
            $course["description"] ?? ""
        )); ?>
    </p>

    <p>
        <strong>Tổng số bài học:</strong>
        <?php echo $totalLessons; ?>
    </p>

<p>
    <a
        href="progress.php?course_id=<?php echo $courseId; ?>"
        class="btn btn-primary"
    >
        Xem tiến độ học tập
    </a>
</p>



    <h2 style="margin-top: 30px;">
        Danh sách bài học
    </h2>

    <div style="overflow-x: auto;">

        <table class="data-table">

            <thead>
                <tr>
                    <th>Thứ tự</th>
                    <th>Tên bài học</th>
                    <th>Trắc nghiệm</th>
                    <th>Thao tác</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($lessons as $lesson): ?>

                    <?php
                        $hasQuiz = (
                            !empty($lesson["quiz_id"]) &&
                            (int)$lesson["total_questions"] > 0
                        );

                        $hasAttempt = (
                            $lesson["attempt_id"] !== null
                        );
                    ?>

                    <tr>

                        <td>
                            <?php echo (int)$lesson["lesson_order"]; ?>
                        </td>

                        <td>
                            <?php echo e($lesson["title"]); ?>
                        </td>

                        <td>

                            <?php if (!$hasQuiz): ?>

                                Chưa có trắc nghiệm

                            <?php elseif ($hasAttempt): ?>

                                Đã hoàn thành

                                <strong>
                                    <?php echo e(
                                        $lesson["quiz_score"]
                                    ); ?>/10
                                </strong>

                            <?php else: ?>

                                Chưa làm

                                (<?php echo (int)$lesson["total_questions"]; ?>
                                câu hỏi)

                            <?php endif; ?>

                        </td>

                        <td>

                            <a
                                href="lesson.php?lesson_id=<?php
                                    echo (int)$lesson["id"];
                                ?>"
                                class="btn btn-primary"
                            >
                                Xem bài học
                            </a>

                            <?php if ($hasQuiz): ?>

                                <a
                                    href="quiz.php?lesson_id=<?php
                                        echo (int)$lesson["id"];
                                    ?>"
                                    class="btn btn-primary"
                                >

                                    <?php if ($hasAttempt): ?>

                                        Xem kết quả

                                    <?php else: ?>

                                        Làm trắc nghiệm

                                    <?php endif; ?>

                                </a>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

                <?php if (empty($lessons)): ?>

                    <tr>
                        <td colspan="4">
                            Khóa học chưa có bài học nào.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

    <p style="margin-top: 25px;">

        <a href="my_courses.php">
            ← Quay lại Khóa học của tôi
        </a>

    </p>

</div>

</body>
</html>
