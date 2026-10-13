
<?php
require_once __DIR__ . "/../includes/auth.php";

// Chỉ học viên được truy cập
requireRole("student");

// Lấy ID học viên từ Session
$studentId = (int)$_SESSION["user_id"];

// Lấy các khóa học mà học viên đã đăng ký
$stmt = $pdo->prepare(
    "SELECT
        c.id,
        c.title,
        c.description,
        c.category,
        c.teacher_name,
        c.status,
        e.enrolled_at
     FROM enrollments e
     JOIN courses c ON c.id = e.course_id
     WHERE e.student_id = ?
       AND c.status = 'approved'
     ORDER BY e.enrolled_at DESC, e.id DESC"
);

$stmt->execute([$studentId]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Đếm số khóa học
$totalCourses = count($courses);

// Hàm hiển thị dữ liệu an toàn
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

    <title>Khóa học của tôi - EduSpace</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >
</head>

<body>

<div class="container" style="padding: 40px 0;">

    <h1>Khóa học của tôi</h1>

    <p>
        Tổng số khóa học đã đăng ký và đang được phê duyệt:
        <strong><?php echo $totalCourses; ?></strong>
    </p>

    <div style="overflow-x:auto;">

        <table class="data-table">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên khóa học</th>
                    <th>Giảng viên</th>
                    <th>Danh mục</th>
                    <th>Ngày đăng ký</th>
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
                            <?php echo e(
                                $course["category"]
                            ); ?>
                        </td>

                        <td>
                            <?php echo e(
                                $course["enrolled_at"]
                            ); ?>
                        </td>

                        
<td>
    <a
        href="course.php?course_id=<?php echo (int)$course['id']; ?>"
        class="btn btn-primary"
    >
        Vào học
    </a>
</td>


                    </tr>

                <?php endforeach; ?>

                <?php if (empty($courses)): ?>
                    <tr>
                        <td colspan="6">
                            Bạn chưa có khóa học nào.
                            Hãy đăng ký khóa học để bắt đầu học.
                        </td>
                    </tr>
                <?php endif; ?>

            </tbody>
        </table>

    </div>

    <p style="margin-top: 25px;">
        <a href="courses.php">
            Xem thêm khóa học
        </a>
    </p>

    <p style="margin-top: 15px;">
        <a href="../dashboards/student.php">
            ← Quay lại Dashboard học viên
        </a>
    </p>

</div>

</body>
</html>
