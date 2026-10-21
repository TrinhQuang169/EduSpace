
<?php
require_once __DIR__ . "/../includes/auth.php";
requireRole("admin");

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Lấy toàn bộ lớp học, ưu tiên khóa chờ duyệt
$sql = "SELECT id, title, description, category,
               teacher_name, status, created_at
        FROM courses
        ORDER BY
            CASE WHEN status = 'pending' THEN 0 ELSE 1 END,
            id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$courses = $stmt->fetchAll();

$statusNames = [
    "draft" => "Bản nháp",
    "pending" => "Chờ duyệt",
    "approved" => "Đã duyệt",
    "rejected" => "Bị từ chối"
];

$messages = [
    "approved" => "Đã phê duyệt lớp học thành công.",
    "rejected" => "Đã từ chối lớp học."
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

<?php require_once __DIR__ . "/../includes/ui.php"; eduStart('Quản lý lớp học', 'courses'); ?>



    <h1>Quản lý lớp học - Admin</h1>

    <p style="margin: 15px 0 25px;">
        Xem và phê duyệt các lớp học do giáo viên gửi lên.
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

    <div class="edu-table-wrap" role="region" aria-label="Bảng dữ liệu" tabindex="0"><table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên lớp học</th>
                    <th>Giáo viên</th>
                    <th>Danh mục</th>
                    <th>Mô tả</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($courses as $course): ?>
                    <tr>
                        <td><?php echo (int)$course["id"]; ?></td>

                        <td><?php echo e($course["title"]); ?></td>

                        <td><?php echo e(
                            $course["teacher_name"] ?? "Chưa xác định"
                        ); ?></td>

                        <td><?php echo e($course["category"]); ?></td>

                        <td><?php echo nl2br(e(
                            $course["description"] ?? ""
                        )); ?></td>

                        <td><?= eduBadge($course["status"]) ?></td>

                        
<td>
    <a
        href="course_preview.php?course_id=<?php echo (int)$course['id']; ?>"
        class="btn btn-primary"
    >
        Xem nội dung
    </a>

    <?php if ($course["status"] === "pending"): ?>


                                <form
                                    action="review_course.php"
                                    method="POST"
                                    style="display:inline-block;"
                                    onsubmit="return confirm(
                                        'Bạn chắc chắn muốn phê duyệt lớp học?'
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

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="approve"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                    >
                                        Phê duyệt
                                    </button>
                                </form>

                                <form
                                    action="review_course.php"
                                    method="POST"
                                    style="display:inline-block;"
                                    onsubmit="return confirm(
                                        'Bạn chắc chắn muốn từ chối lớp học?'
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

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="reject"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-danger"
                                    >
                                        Từ chối
                                    </button>
                                </form>

                            <?php else: ?>
                                <span>Đã xử lý / Chưa gửi</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($courses)): ?>
                    <tr>
                        <td colspan="7">
                            Chưa có lớp học nào.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table></div>

    <p style="margin-top: 25px;">
        <a href="../dashboards/admin.php">
            ← Quay lại Dashboard Admin
        </a>
    </p>

<?php eduEnd(); ?>
