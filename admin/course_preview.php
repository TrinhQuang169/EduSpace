
<?php
require_once __DIR__ . "/../includes/auth.php";

requireRole("admin");

// Lấy ID lớp học từ URL
$courseId = filter_input(
    INPUT_GET,
    "course_id",
    FILTER_VALIDATE_INT
);

if (!$courseId || $courseId < 1) {
    http_response_code(400);
    exit("ID lớp học không hợp lệ.");
}

// Lấy thông tin lớp học
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
    exit("Không tìm thấy lớp học.");
}

// Lấy tất cả bài học thuộc lớp học
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

<?php require_once __DIR__ . "/../includes/ui.php"; eduStart('Xem nội dung lớp học', 'courses'); ?>



    <h1>Xem trước nội dung lớp học</h1>

    <p>
        <strong>Tên lớp học:</strong>
        <?php echo e($course["title"]); ?>
    </p>

    <p>
        <strong>Trạng thái:</strong>
        <?= eduBadge($course["status"]) ?>
    </p>

    <p>
        <strong>Số bài học:</strong>
        <?php echo count($lessons); ?>
    </p>

    <hr>

    <?php if (empty($lessons)): ?>
        <p>Lớp học chưa có bài học.</p>
    <?php endif; ?>

    <?php foreach ($lessons as $lesson): ?>

        <section class="edu-panel">

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

                <div class="edu-table-wrap" role="region" aria-label="Bảng dữ liệu" tabindex="0"><table class="data-table">
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
                                    <?= eduBadge($file["file_type"]) ?>
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
                </table></div>

            <?php endif; ?>

        </section>

        <hr>

    <?php endforeach; ?>

    <?php if ($course['status'] === 'pending'): ?>
    <section class="edu-panel">
        <h2>Phê duyệt nội dung lớp học</h2>
        <p>Kiểm tra nội dung bài học và tài liệu trước khi đưa ra quyết định.</p>
        <div class="edu-review-actions">
            <form action="review_course.php" method="POST" onsubmit="return confirm('Bạn chắc chắn muốn phê duyệt lớp học?');">
                <input type="hidden" name="csrf_token" value="<?= eduEscape($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="course_id" value="<?= (int)$course['id'] ?>">
                <input type="hidden" name="action" value="approve">
                <button type="submit" class="btn btn-primary"><?= eduIcon('check') ?>Phê duyệt lớp học</button>
            </form>
            <form action="review_course.php" method="POST" onsubmit="return confirm('Bạn chắc chắn muốn từ chối lớp học?');">
                <input type="hidden" name="csrf_token" value="<?= eduEscape($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="course_id" value="<?= (int)$course['id'] ?>">
                <input type="hidden" name="action" value="reject">
                <button type="submit" class="btn btn-danger">Từ chối</button>
            </form>
        </div>
    </section>
    <?php endif; ?>

    <p style="margin-top: 30px;">
        <a href="courses.php">
            ← Quay lại quản lý lớp học
        </a>
    </p>

<?php eduEnd(); ?>
