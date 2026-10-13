
<?php
require_once __DIR__ . "/../includes/auth.php";

requireRole("admin");

// Lấy ID khóa học từ URL
$courseId = filter_input(
    INPUT_GET,
    "course_id",
    FILTER_VALIDATE_INT
);

if (!$courseId || $courseId < 1) {
    http_response_code(400);
    exit("ID khóa học không hợp lệ.");
}

// Lấy thông tin khóa học
$stmt = $pdo->prepare(
    "SELECT id, title, status
     FROM courses
     WHERE id = ?
     LIMIT 1"
);

$stmt->execute([$courseId]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    http_response_code(404);
    exit("Không tìm thấy khóa học.");
}

// Lấy tất cả bài học thuộc khóa học
$stmt = $pdo->prepare(
    "SELECT id, title, content, video_url, lesson_order
     FROM lessons
     WHERE course_id = ?
     ORDER BY lesson_order ASC, id ASC"
);

$stmt->execute([$courseId]);
$lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy tất cả tài liệu thuộc các bài học
$stmt = $pdo->prepare(
    "SELECT
        m.id,
        m.lesson_id,
        m.file_name,
        m.file_type,
        m.file_size
     FROM lesson_materials m
     JOIN lessons l ON l.id = m.lesson_id
     WHERE l.course_id = ?
     ORDER BY m.id ASC"
);

$stmt->execute([$courseId]);
$allMaterials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Nhóm tài liệu theo ID bài học
$materialsByLesson = [];

foreach ($allMaterials as $material) {
    $lessonId = (int)$material["lesson_id"];
    $materialsByLesson[$lessonId][] = $material;
}

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
    <title>Xem trước khóa học - EduSpace</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container" style="padding: 40px 0;">

    <h1>Xem trước nội dung khóa học</h1>

    <p>
        <strong>Tên khóa học:</strong>
        <?php echo e($course["title"]); ?>
    </p>

    <p>
        <strong>Trạng thái:</strong>
        <?php echo e($course["status"]); ?>
    </p>

    <p>
        <strong>Số bài học:</strong>
        <?php echo count($lessons); ?>
    </p>

    <hr>

    <?php if (empty($lessons)): ?>
        <p>Khóa học chưa có bài học.</p>
    <?php endif; ?>

    <?php foreach ($lessons as $lesson): ?>

        <section style="margin: 30px 0;">

            <h2>
                Bài <?php echo (int)$lesson["lesson_order"]; ?>:
                <?php echo e($lesson["title"]); ?>
            </h2>

            <h3>Nội dung văn bản</h3>

            <?php if (trim((string)$lesson["content"]) !== ""): ?>
                <div>
                    <?php
                        echo nl2br(e($lesson["content"]));
                    ?>
                </div>
            <?php else: ?>
                <p>Không có nội dung văn bản.</p>
            <?php endif; ?>

            <h3 style="margin-top: 20px;">Video YouTube</h3>

            <?php
                $videoUrl = trim(
                    (string)($lesson["video_url"] ?? "")
                );

                $videoHost = strtolower(
                    (string)parse_url(
                        $videoUrl,
                        PHP_URL_HOST
                    )
                );

                $validYoutube =
                    filter_var($videoUrl, FILTER_VALIDATE_URL) &&
                    strtolower(
                        (string)parse_url(
                            $videoUrl,
                            PHP_URL_SCHEME
                        )
                    ) === "https" &&
                    in_array(
                        $videoHost,
                        [
                            "youtube.com",
                            "www.youtube.com",
                            "youtu.be"
                        ],
                        true
                    );
            ?>

            <?php if ($validYoutube): ?>
                <p>
                    <a
                        href="<?php echo e($videoUrl); ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Mở video YouTube
                    </a>
                </p>
            <?php else: ?>
                <p>Không có liên kết YouTube hợp lệ.</p>
            <?php endif; ?>

            <h3 style="margin-top: 20px;">
                Tài liệu bài học
            </h3>

            <?php
                $files = $materialsByLesson[
                    (int)$lesson["id"]
                ] ?? [];
            ?>

            <?php if (empty($files)): ?>
                <p>Không có tài liệu đính kèm.</p>
            <?php else: ?>

                <table border="1" cellpadding="8">
                    <thead>
                        <tr>
                            <th>Tên tài liệu</th>
                            <th>Loại</th>
                            <th>Dung lượng</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($files as $file): ?>
                            <tr>
                                <td>
                                    <?php echo e(
                                        $file["file_name"]
                                    ); ?>
                                </td>

                                <td>
                                    <?php echo e(
                                        strtoupper(
                                            $file["file_type"]
                                        )
                                    ); ?>
                                </td>

                                <td>
                                    <?php echo round(
                                        $file["file_size"] / 1048576,
                                        2
                                    ); ?> MB
                                </td>

                                <td>
                                    <?php if (in_array(
                                        $file["file_type"],
                                        ["pdf", "video"],
                                        true
                                    )): ?>
                                        <a
                                            href="view_material.php?id=<?php echo (int)$file['id']; ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            Xem
                                        </a>
                                    <?php endif; ?>

                                    <a
                                        href="view_material.php?id=<?php echo (int)$file['id']; ?>&download=1"
                                    >
                                        Tải
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php endif; ?>

        </section>

        <hr>

    <?php endforeach; ?>

    <p style="margin-top: 30px;">
        <a href="courses.php">
            ← Quay lại quản lý khóa học
        </a>
    </p>

</div>

</body>
</html>
