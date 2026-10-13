
<?php
require_once __DIR__ . "/../includes/auth.php";
requireRole("teacher");

$teacherId = (int)$_SESSION["user_id"];

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$stmt = $pdo->prepare(
    "SELECT id, title, category, status, created_at
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
    "updated" => "Đã cập nhật khóa học thành công.",
    "submitted" => "Đã gửi khóa học cho Admin phê duyệt.",
    "deleted" => "Đã xóa khóa học bản nháp thành công."
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
    <title>Khóa học của tôi - EduSpace</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container" style="padding: 50px 0;">

    <h1>📚 Khóa học của tôi</h1>

    <p style="margin: 15px 0 25px;">
        Quản lý các khóa học bạn đã tạo.
    </p>

    <?php if ($message !== ""): ?>
        <div class="alert alert-success">
            <?php echo e($message); ?>
        </div>
    <?php endif; ?>

    <a href="create_course.php"
       class="btn btn-primary">
        + Tạo khóa học mới
    </a>

    <div style="margin-top: 30px; overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên khóa học</th>
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

                        <td><?php echo e($course["title"]); ?></td>

                        <td><?php echo e($course["category"]); ?></td>

                        <td>
                            <?php echo e(
                                $statusNames[$course["status"]]
                                ?? $course["status"]
                            ); ?>
                        </td>

                        <td><?php echo e($course["created_at"]); ?></td>

                        <td>
                            
                        <a
                                 href="lessons.php?course_id=<?php echo (int)$course["id"]; ?>"
                                  class="btn btn-primary"
                        >   
                        Bài học
                        </a>

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
                                        'Bạn chắc chắn muốn gửi duyệt khóa học này?'
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
                        <td colspan="6">
                            Bạn chưa tạo khóa học nào.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <p style="margin-top: 25px;">
        <a href="../dashboards/teacher.php">
            ← Quay lại Dashboard
        </a>
    </p>

</div>

</body>
</html>
