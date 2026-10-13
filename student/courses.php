
<?php
require_once __DIR__ . "/../includes/auth.php";

requireRole("student");

$studentId = (int)$_SESSION["user_id"];

// Tạo CSRF token cho biểu mẫu đăng ký
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Chỉ lấy khóa học đã được Admin duyệt
// Đồng thời kiểm tra học viên đã đăng ký chưa
$stmt = $pdo->prepare(
    "SELECT
        c.id,
        c.title,
        c.description,
        c.category,
        c.teacher_name,
        c.status,
        EXISTS (
            SELECT 1
            FROM enrollments e
            WHERE e.course_id = c.id
              AND e.student_id = ?
        ) AS is_enrolled
     FROM courses c
     WHERE c.status = 'approved'
     ORDER BY c.id DESC"
);

$stmt->execute([$studentId]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = "";

$messages = [
    "enrolled" => "Đăng ký khóa học thành công!",
    "already" => "Bạn đã đăng ký khóa học này rồi."
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
    <title>Khóa học - EduSpace</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container" style="padding: 40px 0;">

    <h1>Danh sách khóa học</h1>

    <p>
        Những khóa học đã được Admin phê duyệt.
    </p>

    <?php if ($message !== ""): ?>
        <div class="alert alert-success">
            <?php echo e($message); ?>
        </div>
    <?php endif; ?>

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên khóa học</th>
                    <th>Giảng viên</th>
                    <th>Danh mục</th>
                    <th>Mô tả</th>
                    <th>Trạng thái đăng ký</th>
                    <th>Thao tác</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($courses as $course): ?>

                    <tr>
                        <td>
                            <?php echo (int)$course["id"]; ?>
                        </td>

                        <td>
                            <?php echo e($course["title"]); ?>
                        </td>

                        <td>
                            <?php echo e(
                                $course["teacher_name"] ??
                                "Chưa xác định"
                            ); ?>
                        </td>

                        <td>
                            <?php echo e($course["category"]); ?>
                        </td>

                        <td>
                            <?php echo nl2br(e(
                                $course["description"] ?? ""
                            )); ?>
                        </td>

                        <td>
                            <?php if ($course["is_enrolled"]): ?>
                                Đã đăng ký
                            <?php else: ?>
                                Chưa đăng ký
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if ($course["is_enrolled"]): ?>

                                <span>
                                    Đã tham gia
                                </span>

                            <?php else: ?>

                                <form
                                    action="enroll.php"
                                    method="POST"
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
                                        Đăng ký học
                                    </button>
                                </form>

                            <?php endif; ?>
                        </td>
                    </tr>

                <?php endforeach; ?>

                <?php if (empty($courses)): ?>
                    <tr>
                        <td colspan="7">
                            Hiện chưa có khóa học được phê duyệt.
                        </td>
                    </tr>
                <?php endif; ?>

            </tbody>
        </table>
    </div>

    <p style="margin-top: 25px;">
        <a href="../dashboards/student.php">
            ← Quay lại Dashboard học viên
        </a>
    </p>

</div>

</body>
</html>
