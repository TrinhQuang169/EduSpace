
<?php
require_once __DIR__ . "/../includes/auth.php";
requireRole("teacher");

// Lấy ID lớp học từ URL
$courseId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$courseId || $courseId < 1) {
    http_response_code(400);
    exit("ID lớp học không hợp lệ.");
}

$teacherId = (int)$_SESSION["user_id"];

// Chỉ lấy lớp học thuộc giáo viên đang đăng nhập
$stmt = $pdo->prepare(
    "SELECT *
     FROM courses
     WHERE id = ? AND teacher_id = ?
     LIMIT 1"
);

$stmt->execute([$courseId, $teacherId]);
$course = $stmt->fetch();

if (!$course) {
    http_response_code(404);
    exit("Không tìm thấy lớp học của bạn.");
}

// Chỉ cho sửa bản nháp hoặc lớp học bị từ chối
$editableStatuses = ["draft", "rejected"];

if (!in_array($course["status"], $editableStatuses, true)) {
    http_response_code(403);
    exit("Lớp học này hiện không được phép chỉnh sửa.");
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$error = "";

$title = $course["title"];
$description = $course["description"] ?? "";
$category = $course["category"];
$icon = $course["icon"] ?? "📚";
$color = $course["color"] ?? "blue";

$allowedIcons = ["📚", "💻", "🎨", "⚡", "🐍", "🤖"];
$allowedColors = [
    "blue", "purple", "yellow",
    "green", "orange", "pink"
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        http_response_code(403);
        exit("Yêu cầu không hợp lệ.");
    }

    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $icon = $_POST["icon"] ?? "📚";
    $color = $_POST["color"] ?? "blue";

    if ($title === "" || $category === "") {
        $error = "Vui lòng nhập tên và danh mục lớp học.";

    } elseif (mb_strlen($title) > 255) {
        $error = "Tên lớp học không được vượt quá 255 ký tự.";

    } elseif (mb_strlen($category) > 100) {
        $error = "Danh mục không được vượt quá 100 ký tự.";

    } elseif (mb_strlen($description) > 5000) {
        $error = "Mô tả không được vượt quá 5000 ký tự.";

    } elseif (!in_array($icon, $allowedIcons, true)) {
        $error = "Biểu tượng không hợp lệ.";

    } elseif (!in_array($color, $allowedColors, true)) {
        $error = "Màu lớp học không hợp lệ.";

    } else {

        // Kiểm tra lại trạng thái ngay lúc cập nhật
        $sql = "UPDATE courses
                SET title = ?,
                    description = ?,
                    category = ?,
                    icon = ?,
                    color = ?
                WHERE id = ?
                  AND teacher_id = ?
                  AND status IN ('draft', 'rejected')";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $title,
            $description,
            $category,
            $icon,
            $color,
            $courseId,
            $teacherId
        ]);

        // rowCount có thể bằng 0 nếu dữ liệu không đổi,
        // nên kiểm tra lại trạng thái thay vì coi đó là lỗi.
        $check = $pdo->prepare(
            "SELECT status
             FROM courses
             WHERE id = ? AND teacher_id = ?"
        );
        $check->execute([$courseId, $teacherId]);
        $current = $check->fetch();

        if (
            !$current ||
            !in_array(
                $current["status"],
                $editableStatuses,
                true
            )
        ) {
            http_response_code(409);
            exit("Trạng thái lớp học đã thay đổi. Vui lòng tải lại.");
        }

        header(
            "Location: courses.php?message=updated"
        );
        exit;
    }
}

function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<?php require_once __DIR__ . "/../includes/ui.php"; eduStart('Chỉnh sửa lớp học', 'courses'); ?>


    <div class="edu-form-card">



        <h1>Chỉnh sửa lớp học</h1>

        <p class="auth-subtitle">
            Cập nhật thông tin lớp học của bạn
        </p>

        <?php if ($error !== ""): ?>
            <div class="alert alert-error">
                <?php echo e($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo e($_SESSION["csrf_token"]); ?>"
            >

            <div class="form-group">
                <label for="title">Tên lớp học</label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    maxlength="255"
                    value="<?php echo e($title); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="description">Mô tả</label>
                <textarea
                    id="description"
                    name="description"
                    rows="5"
                    maxlength="5000"
                ><?php echo e($description); ?></textarea>
            </div>

            <div class="form-group">
                <label for="category">Danh mục</label>
                <input
                    type="text"
                    id="category"
                    name="category"
                    maxlength="100"
                    value="<?php echo e($category); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="icon">Biểu tượng</label>
                <select id="icon" name="icon">
                    <?php foreach ($allowedIcons as $item): ?>
                        <option
                            value="<?php echo e($item); ?>"
                            <?php echo $icon === $item
                                ? "selected" : ""; ?>
                        >
                            <?php echo e($item); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="color">Màu sắc</label>
                <select id="color" name="color">
                    <?php foreach ($allowedColors as $item): ?>
                        <option
                            value="<?php echo e($item); ?>"
                            <?php echo $color === $item
                                ? "selected" : ""; ?>
                        >
                            <?php echo e($item); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button
                type="submit"
                class="btn btn-primary auth-submit"
            >
                Lưu thay đổi
            </button>

        </form>

        <p class="auth-back">
            <a href="courses.php">← Quay lại danh sách</a>
        </p>

    </div>
<?php eduEnd(); ?>
