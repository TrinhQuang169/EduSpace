
<?php
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../config/storage.php";

requireRole("teacher");

// Lấy ID tài liệu trên đường dẫn
$materialId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$materialId || $materialId < 1) {
    http_response_code(400);
    exit("ID tài liệu không hợp lệ.");
}

$teacherId = (int)$_SESSION["user_id"];

// Chỉ lấy tài liệu thuộc khóa học của giảng viên
$stmt = $pdo->prepare(
    "SELECT
        m.id,
        m.file_name,
        m.stored_name,
        m.file_type,
        l.course_id
     FROM lesson_materials m
     JOIN lessons l ON l.id = m.lesson_id
     JOIN courses c ON c.id = l.course_id
     WHERE m.id = ?
       AND c.teacher_id = ?
     LIMIT 1"
);

$stmt->execute([$materialId, $teacherId]);
$material = $stmt->fetch();

if (!$material) {
    http_response_code(404);
    exit("Không tìm thấy tài liệu hoặc không có quyền xem.");
}

// Chỉ cho phép tên file đã tạo bằng mã ngẫu nhiên
$storedName = $material["stored_name"];

if (!preg_match(
    '/^[a-f0-9]{32}\.(docx|pdf|mp4)$/',
    $storedName
)) {
    http_response_code(400);
    exit("Tên file lưu trữ không hợp lệ.");
}

$path = rtrim(
    EDUSPACE_STORAGE_PATH,
    "/\\"
) . DIRECTORY_SEPARATOR . $storedName;

if (!is_file($path) || !is_readable($path)) {
    http_response_code(404);
    exit("File không tồn tại trên máy chủ.");
}

// Kiểm tra phần mở rộng và loại file lưu trong DB
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

$size = filesize($path);

if ($size === false) {
    http_response_code(500);
    exit("Không thể đọc file.");
}

// ?download=1 là tải xuống
// Mặc định: PDF/MP4 mở trực tiếp
$download = ($_GET["download"] ?? "") === "1";

// DOCX nên tải về vì trình duyệt không đọc trực tiếp
if ($extension === "docx") {
    $download = true;
}

// Loại bỏ các ký tự điều khiển khỏi tên gốc
$originalName = preg_replace(
    '/[\x00-\x1F\x7F]/',
    "",
    $material["file_name"]
);

// Dùng tên dự phòng ASCII cho Content-Disposition
$fallbackName = "material." . $extension;

$disposition = $download ? "attachment" : "inline";

$mime = $types[$extension]["mime"];

header("Content-Type: " . $mime);
header("X-Content-Type-Options: nosniff");
header("Cache-Control: private, no-store");
header(
    "Content-Disposition: " . $disposition .
    '; filename="' . $fallbackName . '"' .
    "; filename*=UTF-8''" . rawurlencode($originalName)
);

// Giải phóng session để không giữ khóa khi phát video
session_write_close();

// Hỗ trợ xem MP4 và tua video bằng HTTP Range
if ($extension === "mp4" && !$download && $size > 0) {

    $start = 0;
    $end = $size - 1;

    header("Accept-Ranges: bytes");

    if (isset($_SERVER["HTTP_RANGE"])) {

        $range = $_SERVER["HTTP_RANGE"];

        if (!preg_match(
            '/^bytes=(\d*)-(\d*)$/',
            $range,
            $matches
        ) || ($matches[1] === "" && $matches[2] === "")) {
            http_response_code(416);
            header("Content-Range: bytes */" . $size);
            exit;
        }

        if ($matches[1] === "") {
            // Ví dụ: bytes=-500 (500 byte cuối)
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

// PDF và file tải xuống
header("Content-Length: " . $size);

readfile($path);
exit;
