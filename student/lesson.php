
<?php
require_once __DIR__ . "/../includes/auth.php";

// Chỉ học sinh được truy cập
requireRole("student");

// 1. Nhận ID bài học từ URL
$lessonId = filter_input(
    INPUT_GET,
    "lesson_id",
    FILTER_VALIDATE_INT
);

if (!$lessonId || $lessonId < 1) {
    http_response_code(400);
    exit("ID bài học không hợp lệ.");
}

// 2. Lấy ID học sinh đang đăng nhập
$studentId = (int)$_SESSION["user_id"];

// 3. Kiểm tra học sinh có quyền xem bài học
// Phải tham gia lớp học và lớp học được duyệt
$stmt = $pdo->prepare(
    "SELECT
        l.id,
        l.course_id,
        l.title AS lesson_title,
        l.content,
        l.video_url,
        l.lesson_order,
        c.title AS course_title
     FROM lessons l
     INNER JOIN courses c
         ON c.id = l.course_id
     INNER JOIN enrollments e
         ON e.course_id = c.id
     WHERE l.id = ?
       AND e.student_id = ?
       AND c.status = 'approved'
     LIMIT 1"
);

$stmt->execute([$lessonId, $studentId]);
$lesson = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lesson) {
    http_response_code(403);
    exit(
        "Bạn chưa tham gia lớp học này hoặc không có quyền xem bài học."
    );
}

$courseId = (int)$lesson["course_id"];

// 4. Lấy tài liệu đính kèm bài học
$stmt = $pdo->prepare(
    "SELECT
        id,
        file_name,
        file_type,
        file_size
     FROM lesson_materials
     WHERE lesson_id = ?
     ORDER BY id ASC"
);

$stmt->execute([$lessonId]);
$materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. Kiểm tra liên kết YouTube
$videoUrl = trim((string)($lesson["video_url"] ?? ""));

$videoHost = strtolower(
    (string)parse_url($videoUrl, PHP_URL_HOST)
);

$videoScheme = strtolower(
    (string)parse_url($videoUrl, PHP_URL_SCHEME)
);

$hasValidYoutube = (
    $videoUrl !== "" &&
    filter_var($videoUrl, FILTER_VALIDATE_URL) &&
    $videoScheme === "https" &&
    in_array(
        $videoHost,
        ["youtube.com", "www.youtube.com", "youtu.be"],
        true
    )
);

// 6. Hàm hiển thị dữ liệu HTML an toàn
function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}



// Kiểm tra bài học này có trắc nghiệm hay không
$stmt = $pdo->prepare(
    "SELECT id FROM quizzes WHERE lesson_id = ? LIMIT 1"
);
$stmt->execute([$lessonId]);
$hasQuiz = (bool)$stmt->fetchColumn();
?>
<?php require_once __DIR__ . "/../includes/ui.php"; eduStart('Không gian học tập', 'my-courses'); ?>



    <p>
        <a href="course.php?course_id=<?php echo $courseId; ?>">
            ← Quay lại danh sách bài học
        </a>
    </p>

    <h1>
        Bài <?php echo (int)$lesson["lesson_order"]; ?>:
        <?php echo e($lesson["lesson_title"]); ?>
    </h1>

    <p>
        <strong>Lớp học:</strong>
        <?php echo e($lesson["course_title"]); ?>
    </p>

    <hr>

    <h2>Nội dung bài học</h2>

    <?php if (
        trim((string)($lesson["content"] ?? "")) !== ""
    ): ?>

        <div class="edu-reading">
            <?php echo e($lesson["content"]); ?>
        </div>

    <?php else: ?>

        <p>Giáo viên không cung cấp nội dung văn bản.</p>

    <?php endif; ?>

    <hr>

    <h2>Video YouTube</h2>

    <?php if ($hasValidYoutube): ?>

        <p>
            <a
                href="<?php echo e($videoUrl); ?>"
                target="_blank"
                rel="noopener noreferrer"
            >
                Mở video bài học trên YouTube
            </a>
        </p>

    <?php else: ?>

        <p>Bài học không có video YouTube.</p>

    <?php endif; ?>

    <hr>

    <h2>Tài liệu đính kèm</h2>

    <?php if (empty($materials)): ?>

        <p>Bài học không có tài liệu đính kèm.</p>

    <?php else: ?>

        <div class="edu-table-wrap" role="region" aria-label="Bảng dữ liệu" tabindex="0"><table class="data-table">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên tài liệu</th>
                        <th>Loại</th>
                        <th>Dung lượng</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($materials as $material): ?>

                        <tr>

                            <td>
                                <?php echo (int)$material["id"]; ?>
                            </td>

                            <td>
                                <?php echo e($material["file_name"]); ?>
                            </td>

                            <td>
                                <?= eduBadge($material["file_type"]) ?>
                            </td>

                            <td>
                                <?php echo round(
                                    $material["file_size"] / 1048576,
                                    2
                                ); ?> MB
                            </td>

                            
<td>
    <?php if (in_array(
        $material["file_type"],
        ["pdf", "video"],
        true
    )): ?>

        <a
            href="view_material.php?id=<?php echo (int)$material['id']; ?>"
            target="_blank"
            rel="noopener noreferrer"
        >
            <?php echo $material["file_type"] === "video"
                ? "Xem video"
                : "Xem PDF"; ?>
        </a>

        |
    <?php endif; ?>

    <a
        href="view_material.php?id=<?php echo (int)$material['id']; ?>&download=1"
    >
        Tải xuống
    </a>





</td>


                        </tr>

                    <?php endforeach; ?>

                </tbody>
            </table></div>

    <?php endif; ?>

    <?php if ($hasQuiz): ?>
    <p style="margin-top:25px">
        <a
            href="quiz.php?lesson_id=<?php echo $lessonId; ?>"
            class="btn btn-primary"
        >
            Làm bài trắc nghiệm
        </a>
    </p>
<?php endif; ?>
<p style="margin-top: 30px;">
        <a href="course.php?course_id=<?php echo $courseId; ?>">
            ← Quay lại lớp học
        </a>
    </p>

<?php eduEnd(); ?>
