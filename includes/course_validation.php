
<?php

/**
 * Kiểm tra nội dung lớp học trước khi gửi duyệt.
 *
 * Kết quả:
 * - ok: true/false
 * - message: thông báo cho giáo viên
 *
 * Hàm chỉ kiểm tra, không thay đổi dữ liệu.
 */
function validateCourseContent(PDO $pdo, int $courseId): array
{
    // 1. Lấy toàn bộ bài học của lớp học
    $stmt = $pdo->prepare(
        "SELECT id, title, content, video_url
         FROM lessons
         WHERE course_id = ?
         ORDER BY lesson_order ASC, id ASC"
    );

    $stmt->execute([$courseId]);
    $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Lớp học phải có ít nhất 1 bài học
    if (count($lessons) === 0) {
        return [
            "ok" => false,
            "message" => "Lớp học phải có ít nhất một bài học trước khi gửi duyệt."
        ];
    }

    // 3. Kiểm tra từng bài học
    $materialStmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM lesson_materials
         WHERE lesson_id = ?"
    );

    foreach ($lessons as $lesson) {

        $lessonId = (int)$lesson["id"];

        // Bài học có nội dung văn bản?
        $hasText = trim(
            (string)($lesson["content"] ?? "")
        ) !== "";

        // Bài học có link video YouTube?
        $hasYoutube = trim(
            (string)($lesson["video_url"] ?? "")
        ) !== "";

        // Bài học có tài liệu Word/PDF/MP4?
        $materialStmt->execute([$lessonId]);

        $materialCount = (int)$materialStmt->fetchColumn();

        $hasMaterial = $materialCount > 0;

        // Chỉ cần ít nhất 1 loại nội dung
        if (!$hasText && !$hasYoutube && !$hasMaterial) {
            return [
                "ok" => false,
                "message" =>
                    "Bài học \"" . $lesson["title"] .
                    "\" chưa có nội dung. Hãy thêm văn bản, " .
                    "video YouTube hoặc tài liệu Word/PDF/MP4."
            ];
        }
    }

    // 4. Tất cả bài học đạt yêu cầu
    return [
        "ok" => true,
        "message" => "Nội dung lớp học hợp lệ."
    ];
}
