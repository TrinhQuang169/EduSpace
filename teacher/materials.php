
<?php
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../config/storage.php";

requireRole("teacher");

// 1. Kiểm tra ID bài học trên URL
$lessonId = filter_input(
    INPUT_GET,
    "lesson_id",
    FILTER_VALIDATE_INT
);

if (!$lessonId || $lessonId < 1) {
    http_response_code(400);
    exit("ID bài học không hợp lệ.");
}

$teacherId = (int)$_SESSION["user_id"];

// 2. Kiểm tra bài học thuộc giảng viên đang đăng nhập
$stmt = $pdo->prepare(
    "SELECT
        l.id,
        l.title AS lesson_title,
        l.course_id,
        c.title AS course_title,
        c.status
     FROM lessons l
     JOIN courses c ON l.course_id = c.id
     WHERE l.id = ?
       AND c.teacher_id = ?
     LIMIT 1"
);

$stmt->execute([$lessonId, $teacherId]);
$lesson = $stmt->fetch();

if (!$lesson) {
    http_response_code(404);
    exit("Không tìm thấy bài học của bạn.");
}

$courseId = (int)$lesson["course_id"];

$canUpload = in_array(
    $lesson["status"],
    ["draft", "rejected"],
    true
);

// 3. Tạo mã CSRF
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$error = "";
$success = "";

$messages = [
    "uploaded" => "Tải tài liệu lên thành công!",
    "deleted" => "Xóa tài liệu thành công!"
];

$messageKey = $_GET["message"] ?? "";
$success = $messages[$messageKey] ?? "";

// 4. Xử lý khi giảng viên nhấn Tải lên
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        http_response_code(403);
        exit("Yêu cầu không hợp lệ.");
    }

    if (!$canUpload) {
        http_response_code(403);
        exit("Khóa học này không được phép thêm tài liệu.");
    }

    // Chuyển $_FILES thành danh sách dễ xử lý
    $files = [];

    if (!isset($_FILES["materials"])) {
        $error = "Không nhận được file. Hãy kiểm tra cấu hình PHP.";
    } else {
        $uploaded = $_FILES["materials"];

        if (!is_array($uploaded["name"])) {
            $error = "Dữ liệu upload không hợp lệ.";
        } else {
            foreach ($uploaded["name"] as $i => $name) {
                $uploadError = $uploaded["error"][$i];

                // Bỏ qua ô chọn file trống
                if ($uploadError === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                $files[] = [
                    "name" => $name,
                    "tmp_name" => $uploaded["tmp_name"][$i],
                    "size" => $uploaded["size"][$i],
                    "error" => $uploadError
                ];
            }
        }
    }

    if ($error === "" && count($files) === 0) {
        $error = "Vui lòng chọn ít nhất một tài liệu.";
    }

    if ($error === "" && count($files) > MAX_FILES_PER_LESSON) {
        $error = "Bạn đã chọn quá nhiều file trong một lần.";
    }

    // 5. Kiểm tra từng file trước khi lưu
    $validFiles = [];

    $allowedTypes = [
        "docx" => [
            "type" => "word",
            "max" => MAX_WORD_SIZE,
            "mimes" => [
                "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                "application/zip"
            ]
        ],
        "pdf" => [
            "type" => "pdf",
            "max" => MAX_PDF_SIZE,
            "mimes" => ["application/pdf"]
        ],
        "mp4" => [
            "type" => "video",
            "max" => MAX_VIDEO_SIZE,
            "mimes" => ["video/mp4"]
        ]
    ];

    if ($error === "") {
        $finfo = new finfo(FILEINFO_MIME_TYPE);

        foreach ($files as $file) {

            if ($file["error"] !== UPLOAD_ERR_OK) {
                $error = "Một file tải lên bị lỗi hoặc quá giới hạn PHP.";
                break;
            }

            if (!is_uploaded_file($file["tmp_name"])) {
                $error = "File tải lên không hợp lệ.";
                break;
            }

            $originalName = basename(
                str_replace("\\", "/", $file["name"])
            );

            $extension = strtolower(
                pathinfo($originalName, PATHINFO_EXTENSION)
            );

            if (
                $originalName === "" ||
                strlen($originalName) > 255 ||
                !isset($allowedTypes[$extension])
            ) {
                $error = "Chỉ hỗ trợ DOCX, PDF và MP4.";
                break;
            }

            $rule = $allowedTypes[$extension];

            $actualSize = filesize($file["tmp_name"]);

            if (
                $actualSize === false ||
                $actualSize <= 0 ||
                $actualSize > $rule["max"]
            ) {
                $error = "File " . $originalName .
                         " có dung lượng không hợp lệ hoặc quá lớn.";
                break;
            }

            $mime = $finfo->file($file["tmp_name"]);

            if (!in_array($mime, $rule["mimes"], true)) {
                $error = "Định dạng thực tế của " .
                         $originalName . " không hợp lệ.";
                break;
            }

            // DOCX thực chất là một tệp ZIP có cấu trúc riêng.
            // Kiểm tra thành phần chính để tránh nhận ZIP bất kỳ.
            if ($extension === "docx") {
                if (!class_exists("ZipArchive")) {
                    $error = "PHP chưa bật extension ZIP.";
                    break;
                }

                $zip = new ZipArchive();

                if (
                    $zip->open($file["tmp_name"]) !== true
                ) {
                    $error = "File Word không hợp lệ.";
                    break;
                }

                $isDocx =
                    $zip->locateName("[Content_Types].xml") !== false &&
                    $zip->locateName("word/document.xml") !== false;

                $zip->close();

                if (!$isDocx) {
                    $error = "File Word không đúng định dạng DOCX.";
                    break;
                }
            }

            $validFiles[] = [
                "name" => $originalName,
                "extension" => $extension,
                "type" => $rule["type"],
                "size" => $actualSize,
                "tmp_name" => $file["tmp_name"]
            ];
        }
    }

    // 6. Lưu dữ liệu
    if ($error === "") {

        $movedPaths = [];

        try {
            $pdo->beginTransaction();

            // Khóa bản ghi khóa học để kiểm tra trạng thái
            $check = $pdo->prepare(
                "SELECT status
                 FROM courses
                 WHERE id = ? AND teacher_id = ?
                 FOR UPDATE"
            );

            $check->execute([$courseId, $teacherId]);
            $currentCourse = $check->fetch();

            if (
                !$currentCourse ||
                !in_array(
                    $currentCourse["status"],
                    ["draft", "rejected"],
                    true
                )
            ) {
                throw new RuntimeException(
                    "Khóa học đã thay đổi trạng thái."
                );
            }

            // Đếm file đã có trong bài học
            $countStmt = $pdo->prepare(
                "SELECT COUNT(*)
                 FROM lesson_materials
                 WHERE lesson_id = ?"
            );

            $countStmt->execute([$lessonId]);
            $existingCount = (int)$countStmt->fetchColumn();

            if (
                $existingCount + count($validFiles)
                > MAX_FILES_PER_LESSON
            ) {
                throw new RuntimeException(
                    "Bài học vượt quá giới hạn 10 tài liệu."
                );
            }

            if (!is_dir(EDUSPACE_STORAGE_PATH)) {
                throw new RuntimeException(
                    "Chưa có thư mục lưu trữ tài liệu."
                );
            }

            if (!is_writable(EDUSPACE_STORAGE_PATH)) {
                throw new RuntimeException(
                    "PHP không có quyền ghi vào thư mục lưu trữ."
                );
            }

            $insert = $pdo->prepare(
                "INSERT INTO lesson_materials
                 (lesson_id, file_name, stored_name,
                  file_type, file_size)
                 VALUES (?, ?, ?, ?, ?)"
            );

            foreach ($validFiles as $file) {

                // Tạo tên ngẫu nhiên, không dùng tên gốc làm đường dẫn
                $storedName = bin2hex(random_bytes(16))
                    . "." . $file["extension"];

                $destination =
                    rtrim(EDUSPACE_STORAGE_PATH, "/\\")
                    . DIRECTORY_SEPARATOR
                    . $storedName;

                if (!move_uploaded_file(
                    $file["tmp_name"],
                    $destination
                )) {
                    throw new RuntimeException(
                        "Không thể lưu file vào ổ đĩa."
                    );
                }

                $movedPaths[] = $destination;

                $insert->execute([
                    $lessonId,
                    $file["name"],
                    $storedName,
                    $file["type"],
                    $file["size"]
                ]);
            }

            $pdo->commit();

            header(
                "Location: materials.php?lesson_id=" .
                $lessonId . "&message=uploaded"
            );
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            // Nếu có lỗi, xóa các file vừa chuyển vào
            foreach ($movedPaths as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }

            error_log($e->getMessage());

$error = "Không thể tải tài liệu lên. Vui lòng thử lại.";
        }
    }
}

// 7. Lấy danh sách tài liệu đã lưu
$stmt = $pdo->prepare(
    "SELECT id, file_name, file_type,
            file_size, created_at
     FROM lesson_materials
     WHERE lesson_id = ?
     ORDER BY id DESC"
);

$stmt->execute([$lessonId]);
$materials = $stmt->fetchAll();

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
    <title>Tài liệu bài học - EduSpace</title>
    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >
</head>
<body>

<div class="container" style="padding: 40px 0;">

    <h1>Quản lý tài liệu bài học</h1>

    <p>
        <strong>Khóa học:</strong>
        <?php echo e($lesson["course_title"]); ?>
    </p>

    <p>
        <strong>Bài học:</strong>
        <?php echo e($lesson["lesson_title"]); ?>
    </p>

    <?php if ($error !== ""): ?>
        <div class="alert alert-error">
            <?php echo e($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success !== ""): ?>
        <div class="alert alert-success">
            <?php echo e($success); ?>
        </div>
    <?php endif; ?>

    <?php if ($canUpload): ?>

        <h2 style="margin-top: 25px;">
            Thêm tài liệu
        </h2>

        <form
            method="POST"
            enctype="multipart/form-data"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo e(
                    $_SESSION["csrf_token"]
                ); ?>"
            >

            <div class="form-group">
                <label for="materials">
                    Chọn Word, PDF hoặc MP4
                </label>

                <input
                    type="file"
                    id="materials"
                    name="materials[]"
                    accept=".docx,.pdf,.mp4"
                    multiple
                    required
                >
            </div>

            <p>
                Word: 10 MB/file,
                PDF: 20 MB/file,
                MP4: 50 MB/file.
                Tối đa 10 file/bài học.
            </p>

            <button
                type="submit"
                class="btn btn-primary"
            >
                Tải tài liệu lên
            </button>
        </form>

    <?php else: ?>

        <p>
            Khóa học đang chờ duyệt hoặc đã duyệt.
            Bạn chỉ có thể xem danh sách tài liệu.
        </p>

    <?php endif; ?>

    <h2 style="margin: 35px 0 15px;">
        Tài liệu đã tải lên
    </h2>

    <div style="overflow-x: auto;">
        <table class="data-table">
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
                            <?php echo e(
                                $material["file_name"]
                            ); ?>
                        </td>

                        <td>
                            <?php echo e(
                                strtoupper($material["file_type"])
                            ); ?>
                        </td>

                        <td>
                            <?php echo round(
                                $material["file_size"] / 1024 / 1024,
                                2
                            ); ?> MB
                        </td>

                        
<td>
    <?php if (
        in_array(
            $material["file_type"],
            ["pdf", "video"],
            true
        )
    ): ?>
        <a
            href="view_material.php?id=<?php echo (int)$material['id']; ?>"
            target="_blank"
            rel="noopener noreferrer"
        >
            Xem
        </a>
    <?php endif; ?>

    <a
        href="view_material.php?id=<?php echo (int)$material['id']; ?>&download=1"
    >
        Tải
    </a>

    <?php if ($canUpload): ?>
        <form
            action="delete_material.php"
            method="POST"
            style="display:inline-block;"
            onsubmit="return confirm('Bạn chắc chắn muốn xóa tài liệu này?');"
        >
            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo e($_SESSION['csrf_token']); ?>"
            >

            <input
                type="hidden"
                name="material_id"
                value="<?php echo (int)$material['id']; ?>"
            >

            <input
                type="hidden"
                name="lesson_id"
                value="<?php echo $lessonId; ?>"
            >

            <button type="submit">
                Xóa
            </button>
        </form>
    <?php endif; ?>
</td>

                    </tr>
                <?php endforeach; ?>

                <?php if (empty($materials)): ?>
                    <tr>
                        <td colspan="5">
    Bài học chưa có tài liệu.
</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <p style="margin-top: 25px;">
        <a href="lessons.php?course_id=<?php
            echo $courseId;
        ?>">
            ← Quay lại danh sách bài học
        </a>
    </p>

</div>
</body>
</html>
