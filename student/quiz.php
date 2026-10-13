
<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
require_once __DIR__ . "/../includes/auth.php";
requireRole("student");

$lessonId = filter_input(
    INPUT_GET, "lesson_id", FILTER_VALIDATE_INT
);

if (!$lessonId || $lessonId < 1) {
    http_response_code(400);
    exit("ID bài học không hợp lệ.");
}

$studentId = (int)$_SESSION["user_id"];

function e($value) {
    return htmlspecialchars(
        (string)$value, ENT_QUOTES, "UTF-8"
    );
}

// Kiểm tra quyền truy cập khóa học
$stmt = $pdo->prepare(
    "SELECT q.id AS quiz_id, q.title AS quiz_title,
            l.title AS lesson_title, l.course_id
     FROM quizzes q
     JOIN lessons l ON l.id = q.lesson_id
     JOIN courses c ON c.id = l.course_id
     JOIN enrollments e ON e.course_id = c.id
     WHERE q.lesson_id = ?
       AND e.student_id = ?
       AND c.status = 'approved'
     LIMIT 1"
);

$stmt->execute([$lessonId, $studentId]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    http_response_code(403);
    exit(
        "Không có bài trắc nghiệm hoặc bạn chưa được phép truy cập."
    );
}

$quizId = (int)$quiz["quiz_id"];
$courseId = (int)$quiz["course_id"];

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Kiểm tra đã làm bài chưa
$stmt = $pdo->prepare(
    "SELECT * FROM quiz_attempts
     WHERE quiz_id = ? AND student_id = ?
     LIMIT 1"
);
$stmt->execute([$quizId, $studentId]);
$attempt = $stmt->fetch(PDO::FETCH_ASSOC);

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        http_response_code(403);
        exit("CSRF không hợp lệ.");
    }

    if ($attempt) {
        header(
            "Location: quiz.php?lesson_id=" . $lessonId
        );
        exit;
    }

    $answers = $_POST["answers"] ?? [];

    if (!is_array($answers)) {
        $error = "Dữ liệu bài làm không hợp lệ.";
    } else {

        try {
            $pdo->beginTransaction();

            // Khóa khóa học, phối hợp với thao tác
            // sửa nội dung của giảng viên.
            $lock = $pdo->prepare(
                "SELECT status FROM courses
                 WHERE id = ?
                 FOR UPDATE"
            );

            $lock->execute([$courseId]);

            if ($lock->fetchColumn() !== "approved") {
                throw new RuntimeException(
                    "Khóa học chưa được phê duyệt."
                );
            }

            // Xác nhận vẫn còn đăng ký
            $check = $pdo->prepare(
                "SELECT COUNT(*) FROM enrollments
                 WHERE course_id = ? AND student_id = ?"
            );
            $check->execute([$courseId, $studentId]);

            if ((int)$check->fetchColumn() !== 1) {
                throw new RuntimeException(
                    "Bạn không còn quyền làm bài."
                );
            }

            // Đọc đáp án chuẩn tại máy chủ
            $stmt = $pdo->prepare(
                "SELECT id, correct_answer
                 FROM quiz_questions
                 WHERE quiz_id = ?
                 ORDER BY question_order, id"
            );
            $stmt->execute([$quizId]);
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $total = count($questions);

            if ($total === 0) {
                throw new RuntimeException(
                    "Bài trắc nghiệm chưa có câu hỏi."
                );
            }

            // Xác nhận học viên đã trả lời mọi câu
            $validIds = [];

            foreach ($questions as $question) {
                $qid = (int)$question["id"];
                $validIds[(string)$qid] = true;

                $selected = $answers[$qid] ?? null;

                if (
                    !is_string($selected) ||
                    !in_array(
                        $selected, ["A", "B", "C", "D"], true
                    )
                ) {
                    throw new InvalidArgumentException(
                        "Bạn cần trả lời đầy đủ tất cả câu hỏi."
                    );
                }
            }

            // Không chấp nhận ID câu hỏi ngoài bài
            foreach ($answers as $qid => $selected) {
                if (!isset($validIds[(string)$qid])) {
                    throw new InvalidArgumentException(
                        "Bài làm chứa câu hỏi không hợp lệ."
                    );
                }
            }

            // Chấm toàn bộ câu hỏi
            $correctCount = 0;
            $graded = [];

            foreach ($questions as $question) {
                $qid = (int)$question["id"];
                $selected = $answers[$qid];

                $isCorrect = (
                    $selected === $question["correct_answer"]
                );

                if ($isCorrect) {
                    $correctCount++;
                }

                $graded[] = [
                    "question_id" => $qid,
                    "selected" => $selected,
                    "is_correct" => $isCorrect ? 1 : 0
                ];
            }

            $score = round(
                $correctCount * 10 / $total, 2
            );

            // Mỗi học viên chỉ có một lượt làm.
            // UNIQUE trong MySQL chặn nộp trùng.
            $stmt = $pdo->prepare(
                "INSERT INTO quiz_attempts
                 (quiz_id, student_id, correct_count,
                  total_questions, score)
                 VALUES (?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $quizId,
                $studentId,
                $correctCount,
                $total,
                $score
            ]);

            $attemptId = (int)$pdo->lastInsertId();

            $insertAnswer = $pdo->prepare(
                "INSERT INTO quiz_answers
                 (attempt_id, question_id,
                  selected_answer, is_correct)
                 VALUES (?, ?, ?, ?)"
            );

            foreach ($graded as $item) {
                $insertAnswer->execute([
                    $attemptId,
                    $item["question_id"],
                    $item["selected"],
                    $item["is_correct"]
                ]);
            }

            $pdo->commit();

            // Chuyển về trang kết quả, tránh nộp lại
            header(
                "Location: quiz.php?lesson_id=" . $lessonId
            );
            exit;

        } catch (InvalidArgumentException $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = $ex->getMessage();

        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($ex->getMessage());

            // Có thể do nhấn nộp hai lần.
            $stmt = $pdo->prepare(
                "SELECT id FROM quiz_attempts
                 WHERE quiz_id = ? AND student_id = ?"
            );
            $stmt->execute([$quizId, $studentId]);

            if ($stmt->fetchColumn()) {
                header(
                    "Location: quiz.php?lesson_id=" . $lessonId
                );
                exit;
            }

            $error = "Không thể nộp bài. Vui lòng thử lại.";
        }
    }
}

// Đọc kết quả sau khi xử lý POST
$stmt = $pdo->prepare(
    "SELECT * FROM quiz_attempts
     WHERE quiz_id = ? AND student_id = ?
     LIMIT 1"
);
$stmt->execute([$quizId, $studentId]);
$attempt = $stmt->fetch(PDO::FETCH_ASSOC);

// Chỉ lấy đáp án đúng và giải thích
// KHI HỌC VIÊN ĐÃ NỘP BÀI.
if ($attempt) {
    $stmt = $pdo->prepare(
        "SELECT
            q.id,
            q.question_text,
            q.option_a, q.option_b,
            q.option_c, q.option_d,
            q.correct_answer,
            q.explanation,
            a.selected_answer,
            a.is_correct
         FROM quiz_answers a
         JOIN quiz_questions q
           ON q.id = a.question_id
         WHERE a.attempt_id = ?
           AND q.quiz_id = ?
         ORDER BY q.question_order, q.id"
    );
    $stmt->execute([
        (int)$attempt["id"], $quizId
    ]);
} else {
    $stmt = $pdo->prepare(
        "SELECT id, question_text,
                option_a, option_b,
                option_c, option_d
         FROM quiz_questions
         WHERE quiz_id = ?
         ORDER BY question_order, id"
    );
    $stmt->execute([$quizId]);
}

$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Trắc nghiệm - EduSpace</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container" style="padding:40px 0">

<h1><?= e($quiz["quiz_title"]) ?></h1>
<p>Bài học: <?= e($quiz["lesson_title"]) ?></p>

<?php if ($error): ?>
<p style="color:red"><?= e($error) ?></p>
<?php endif; ?>

<?php if ($attempt): ?>

<h2>Kết quả bài làm</h2>

<p>
<strong>Điểm: <?= e($attempt["score"]) ?>/10</strong>
</p>

<p>
Số câu đúng:
<?= (int)$attempt["correct_count"] ?> /
<?= (int)$attempt["total_questions"] ?>
</p>

<p>Ngày nộp: <?= e($attempt["submitted_at"]) ?></p>

<?php foreach ($questions as $index => $q): ?>
<hr>

<h3>
Câu <?= $index + 1 ?>:
<?= e($q["question_text"]) ?>
</h3>

<?php foreach (["A","B","C","D"] as $letter): ?>
<p>
<?= $letter ?>.
<?= e($q["option_" . strtolower($letter)]) ?>
</p>
<?php endforeach; ?>

<p>
Bạn đã chọn:
<strong><?= e($q["selected_answer"]) ?></strong>
</p>

<p>
Kết quả:
<strong>
<?= $q["is_correct"] ? "Đúng" : "Sai" ?>
</strong>
</p>

<p>
Đáp án đúng:
<strong><?= e($q["correct_answer"]) ?></strong>
</p>

<p>
Giải thích chi tiết:
<?= nl2br(e($q["explanation"])) ?>
</p>

<?php endforeach; ?>

<?php else: ?>

<?php if (!$questions): ?>
<p>Giảng viên chưa tạo câu hỏi trắc nghiệm.</p>
<?php else: ?>

<p>
Số câu hỏi: <?= count($questions) ?>.
Mỗi câu chọn một đáp án.
</p>

<form method="POST">
<input type="hidden" name="csrf_token"
 value="<?= e($_SESSION["csrf_token"]) ?>">

<?php foreach ($questions as $index => $q): ?>
<hr>

<h3>
Câu <?= $index + 1 ?>:
<?= e($q["question_text"]) ?>
</h3>

<?php foreach (["A","B","C","D"] as $letter): ?>

<label style="display:block;margin:10px 0">
<input
 type="radio"
 name="answers[<?= (int)$q["id"] ?>]"
 value="<?= $letter ?>"
 required
 <?php if (
    ($_POST["answers"][$q["id"]] ?? "") === $letter
 ): ?>checked<?php endif; ?>
>
<?= $letter ?>.
<?= e($q["option_" . strtolower($letter)]) ?>
</label>

<?php endforeach; ?>

<?php endforeach; ?>

<p style="margin-top:25px">
<button
 type="submit"
 class="btn btn-primary"
 onclick="return confirm('Nộp bài trắc nghiệm? Sau khi nộp bạn không thể làm lại.');"
>
 Nộp bài và xem kết quả
</button>
</p>

</form>

<?php endif; ?>

<?php endif; ?>

<p style="margin-top:25px">
<a href="lesson.php?lesson_id=<?= $lessonId ?>">
 ← Quay lại bài học
</a>
</p>

</div>
</body>
</html>
