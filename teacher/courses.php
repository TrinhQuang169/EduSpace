
<?php
require_once __DIR__ . "/../includes/auth.php";
requireRole("teacher");
require_once __DIR__ . '/../includes/classes.php';
$classesReady = eduPrepareClasses($pdo);

$teacherId = (int)$_SESSION["user_id"];

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$stmt = $pdo->prepare(
    "SELECT id, title, category, status, created_at" . ($classesReady ? ", class_code" : ", NULL AS class_code") . "
     FROM courses
     WHERE teacher_id = ?
     ORDER BY id DESC"
);

$stmt->execute([$teacherId]);
$courses = $stmt->fetchAll();

$statusNames = [
    "draft" => "Bản nháp",
    "pending" => "Chờ duyệt",
    "approved" => "Đã duyệt",
    "rejected" => "Bị từ chối"
];


$messages = [
    "updated" => "Đã cập nhật lớp học thành công.",
    "submitted" => "Đã gửi lớp học cho Admin phê duyệt.",
    "deleted" => "Đã xóa lớp học bản nháp thành công."
];


$messageKey = $_GET["message"] ?? "";
$message = $messages[$messageKey] ?? "";

function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<?php require_once __DIR__ . "/../includes/ui.php"; eduStart('Lớp học của tôi', 'courses'); ?>



    <h1>Lớp học của tôi</h1><?php if (!$classesReady): ?><div class="alert alert-error"><?= e(eduClassMigrationMessage()) ?></div><?php endif; ?>

    <p style="margin: 15px 0 25px;">
        Quản lý các lớp học bạn đã tạo.
    </p>

    <div class="edu-stats">
<?php eduStat(count($courses), 'Tổng lớp học', 'book', 'blue');
eduStat(count(array_filter($courses, static fn($item) => $item['status'] === 'draft')), 'Bản nháp', 'file', 'purple');
eduStat(count(array_filter($courses, static fn($item) => $item['status'] === 'pending')), 'Chờ duyệt', 'clock', 'orange');
eduStat(count(array_filter($courses, static fn($item) => $item['status'] === 'approved')), 'Đã duyệt', 'check', 'green'); ?>
</div><?php if ($message !== ""): ?>
        <div class="alert alert-success">
            <?php echo e($message); ?>
        </div>
    <?php endif; ?>

    <a href="create_course.php"
       class="btn btn-primary">
        + Tạo lớp học mới
    </a>

    <div class="edu-table-wrap" role="region" aria-label="Bảng dữ liệu" tabindex="0"><table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên lớp học</th><th>Mã tham gia</th>
                    <th>Danh mục</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th>Thao tác</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($courses as $course): ?>

                    <?php
                    $canEdit = in_array(
                        $course["status"],
                        ["draft", "rejected"],
                        true
                    );
                    ?>

                    <tr>
                        <td><?php echo (int)$course["id"]; ?></td>

                        <td><?php echo e($course["title"]); ?></td><td><code class="edu-class-code"><?= e($course["class_code"] ?? "Chưa sẵn sàng") ?></code></td>

                        <td><?php echo e($course["category"]); ?></td>

                        <td>
                            <?= eduBadge($course["status"]) ?>
                        </td>

                        <td><?php echo e($course["created_at"]); ?></td>

                        <td>
                            
                        <a
                                 href="lessons.php?course_id=<?php echo (int)$course["id"]; ?>"
                                  class="btn btn-primary"
                        >   
                        Bài học
                        </a>

                            <a class="btn btn-primary" href="members.php?course_id=<?= (int)$course["id"] ?>">Thành viên</a>
                            <?php if ($canEdit): ?>

                                <a
                                    class="btn btn-primary"
                                    href="edit_course.php?id=<?php
                                        echo (int)$course["id"];
                                    ?>"
                                >
                                    Sửa
                                </a>

                                <form
                                    method="POST"
                                    action="submit_course.php"
                                    style="display: inline-block;"
                                    onsubmit="return confirm(
                                        'Bạn chắc chắn muốn gửi duyệt lớp học này?'
                                    );"
                                >
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?php echo e(
                                            $_SESSION["csrf_token"]
                                        ); ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="course_id"
                                        value="<?php echo (int)$course["id"]; ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                    >
                                        Gửi duyệt
                                    </button>
                                </form>

                            <?php else: ?>

                                <span>
                                    Không có thao tác
                                </span>

                            <?php endif; ?>
                        </td>
                    </tr>
                
                    
                <?php endforeach; ?>

                <?php if (empty($courses)): ?>
                    <tr>
                        <td colspan="7">
                            Bạn chưa tạo lớp học nào.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table></div>

    <p style="margin-top: 25px;">
        <a href="../dashboards/teacher.php">
            ← Quay lại Dashboard
        </a>
    </p>

<?php eduEnd(); ?>
