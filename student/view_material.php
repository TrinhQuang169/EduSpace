
<?php
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../config/storage.php";

// Chỉ học sinh được truy cập
requireRole("student");

// 1. Nhận ID tài liệu
$materialId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$materialId || $materialId < 1) {
    http_response_code(400);
    exit("ID tài liệu không hợp lệ.");
}

$studentId = (int)$_SESSION["user_id"];

// 2. Kiểm tra quyền truy cập bằng MySQL
// Bắt buộc:
// - Tài liệu thuộc một bài học
// - Bài học thuộc lớp học approved
// - Học sinh đã đăng ký đúng lớp học đó
$stmt = $pdo->prepare(
    "SELECT
        m.id,
        m.file_name,
        m.stored_name,
        m.file_type
     FROM lesson_materials m
     INNER JOIN lessons l
         ON l.id = m.lesson_id
     INNER JOIN courses c
         ON c.id = l.course_id
     INNER JOIN enrollments e
         ON e.course_id = c.id
     WHERE m.id = ?
       AND e.student_id = ?
       AND c.status = 'approved'
     LIMIT 1"
);

$stmt->execute([$materialId, $studentId]);
$material = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$material) {
    http_response_code(403);
    exit(
        "Bạn chưa tham gia lớp học này " .
        "hoặc không có quyền truy cập tài liệu."
    );
}

// 3. Kiểm tra tên file hệ thống
$storedName = $material["stored_name"];

if (!preg_match(
    '/^[a-f0-9]{32}\.(docx|pdf|mp4)$/',
    $storedName
)) {
    http_response_code(400);
    exit("Tên file lưu trữ không hợp lệ.");
}

// 4. Kiểm tra loại file
$extension = strtolower(
    pathinfo($storedName, PATHINFO_EXTENSION)
);

$types = [
    "docx" => [
        "type" => "word",
        "mime" =>
            "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
    ],
    "pdf" => [
        "type" => "pdf",
        "mime" => "application/pdf"
    ],
    "mp4" => [
        "type" => "video",
        "mime" => "video/mp4"
    ]
];

if (
    !isset($types[$extension]) ||
    $types[$extension]["type"] !== $material["file_type"]
) {
    http_response_code(400);
    exit("Loại tài liệu không hợp lệ.");
}

// 5. Xác định đường dẫn file thực tế
$path = rtrim(
    EDUSPACE_STORAGE_PATH,
    "/\\"
) . DIRECTORY_SEPARATOR . $storedName;

if (!is_file($path) || !is_readable($path)) {
    http_response_code(404);
    exit("Không tìm thấy file trên máy chủ.");
}

$size = filesize($path);

if ($size === false) {
    http_response_code(500);
    exit("Không thể đọc file.");
}

// 6. Xác định xem trực tiếp hay tải xuống
$download = ($_GET["download"] ?? "") === "1";

// DOCX luôn tải xuống vì trình duyệt
// thường không xem trực tiếp được
if ($extension === "docx") {
    $download = true;
}

$disposition = $download ? "attachment" : "inline";

// Làm sạch tên file hiển thị
$originalName = preg_replace(
    '/[\x00-\x1F\x7F]/',
    "",
    $material["file_name"]
);

$fallbackName = "material." . $extension;

// 7. Gửi các HTTP Header
header("Content-Type: " . $types[$extension]["mime"]);
header("X-Content-Type-Options: nosniff");
header("Cache-Control: private, no-store");

header(
    "Content-Disposition: " . $disposition .
    '; filename="' . $fallbackName . '"' .
    "; filename*=UTF-8''" . rawurlencode($originalName)
);

// Tránh giữ khóa session khi gửi file lớn
session_write_close();

// 8. Phát MP4 và hỗ trợ tua video
if ($extension === "mp4" && !$download && $size > 0) {

    $start = 0;
    $end = $size - 1;

    header("Accept-Ranges: bytes");

    if (isset($_SERVER["HTTP_RANGE"])) {

        $range = $_SERVER["HTTP_RANGE"];

        if (
            !preg_match(
                '/^bytes=(\d*)-(\d*)$/',
                $range,
                $matches
            ) ||
            (
                $matches[1] === "" &&
                $matches[2] === ""
            )
        ) {
            http_response_code(416);
            header("Content-Range: bytes */" . $size);
            exit;
        }

        if ($matches[1] === "") {

            // Ví dụ bytes=-500: lấy 500 byte cuối
            $suffix = (int)$matches[2];

            if ($suffix <= 0) {
                http_response_code(416);
                header("Content-Range: bytes */" . $size);
                exit;
            }

            $start = max(0, $size - $suffix);

        } else {

            $start = (int)$matches[1];

            if ($matches[2] !== "") {
                $end = min(
                    (int)$matches[2],
                    $size - 1
                );
            }
        }

        if ($start >= $size || $start > $end) {
            http_response_code(416);
            header("Content-Range: bytes */" . $size);
            exit;
        }

        http_response_code(206);

        header(
            "Content-Range: bytes " .
            $start . "-" . $end . "/" . $size
        );
    }

    header("Content-Length: " . ($end - $start + 1));

    $handle = fopen($path, "rb");

    if ($handle === false) {
        exit;
    }

    fseek($handle, $start);

    $remaining = $end - $start + 1;

    while ($remaining > 0 && !feof($handle)) {

        $chunk = fread(
            $handle,
            min(1024 * 1024, $remaining)
        );

        if ($chunk === false || $chunk === "") {
            break;
        }

        echo $chunk;
        $remaining -= strlen($chunk);

        flush();
    }

    fclose($handle);
    exit;
}

// 9. PDF mở trong trình duyệt,
// DOCX và các file yêu cầu tải sẽ tải xuống
header("Content-Length: " . $size);

readfile($path);
exit;
