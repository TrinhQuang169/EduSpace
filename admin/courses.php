
<?php
require_once __DIR__ . "/../includes/auth.php";
requireRole("admin");

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Lấy toàn bộ khóa học, ưu tiên khóa chờ duyệt
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
    "approved" => "Đã phê duyệt khóa học thành công.",
    "rejected" => "Đã từ chối khóa học."
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

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Quản lý khóa học - Admin EduSpace</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<div class="container" style="padding: 50px 0;">

    <h1>🛡️ Quản lý khóa học - Admin</h1>

    <p style="margin: 15px 0 25px;">
        Xem và phê duyệt các khóa học do giảng viên gửi lên.
    </p>

    <?php if ($message !== ""): ?>
        <div class="alert alert-success">
            <?php echo e($message); ?>
        </div>
    <?php endif; ?>

    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên khóa học</th>
                    <th>Giảng viên</th>
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

                        <td><?php echo e(
                            $statusNames[$course["status"]]
                            ?? $course["status"]
                        ); ?></td>

                        
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
                                        'Bạn chắc chắn muốn phê duyệt khóa học?'
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
                                        'Bạn chắc chắn muốn từ chối khóa học?'
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
                            Chưa có khóa học nào.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <p style="margin-top: 25px;">
        <a href="../dashboards/admin.php">
            ← Quay lại Dashboard Admin
        </a>
    </p>

</div>

</body>
</html>
