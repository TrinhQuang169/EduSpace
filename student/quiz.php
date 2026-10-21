
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

// Kiểm tra quyền truy cập lớp học
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

            // Khóa lớp học, phối hợp với thao tác
            // sửa nội dung của giáo viên.
            $lock = $pdo->prepare(
                "SELECT status FROM courses
                 WHERE id = ?
                 FOR UPDATE"
            );

            $lock->execute([$courseId]);

            if ($lock->fetchColumn() !== "approved") {
                throw new RuntimeException(
                    "Lớp học chưa được phê duyệt."
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

            // Xác nhận học sinh đã trả lời mọi câu
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

            // Mỗi học sinh chỉ có một lượt làm.
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
// KHI HỌC SINH ĐÃ NỘP BÀI.
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

<?php require_once __DIR__ . "/../includes/ui.php"; eduStart('Trắc nghiệm & kết quả', 'my-courses'); ?>
<span class="edu-eyebrow">KIỂM TRA KIẾN THỨC</span>
<h1><?= e($quiz["quiz_title"]) ?></h1>
<p>Bài học: <?= e($quiz["lesson_title"]) ?></p>
<?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>

<?php if ($attempt): ?>
<section class="edu-result" aria-label="Kết quả trắc nghiệm">
    <div class="edu-score"><strong><?= e($attempt["score"]) ?></strong><small>trên 10 điểm</small></div>
    <div><span class="edu-eyebrow">ĐÃ HOÀN THÀNH BÀI LÀM</span><h2>Kết quả trắc nghiệm</h2><p>Trả lời đúng <strong><?= (int)$attempt["correct_count"] ?> / <?= (int)$attempt["total_questions"] ?></strong> câu hỏi.</p><p>Ngày nộp: <?= e($attempt["submitted_at"]) ?></p></div>
</section>
<div class="edu-section-heading"><h2>Xem lại câu trả lời</h2></div>
<?php foreach ($questions as $index => $q): ?>
<section class="edu-question">
    <?= eduBadge($q['is_correct'] ? 'complete' : 'rejected', $q['is_correct'] ? 'Trả lời đúng' : 'Trả lời sai') ?>
    <h3>Câu <?= $index + 1 ?>: <?= e($q["question_text"]) ?></h3>
    <?php foreach (["A","B","C","D"] as $letter): ?>
    <div class="edu-answer <?= $letter === $q['correct_answer'] ? 'edu-answer--correct' : ($letter === $q['selected_answer'] ? 'edu-answer--wrong' : '') ?>">
        <span class="edu-answer-letter"><?= $letter ?></span><span><?= e($q["option_" . strtolower($letter)]) ?><?php if ($letter === $q['correct_answer']): ?> <strong> · Đáp án đúng</strong><?php endif; ?><?php if ($letter === $q['selected_answer']): ?> <strong> · Bạn đã chọn</strong><?php endif; ?></span>
    </div>
    <?php endforeach; ?>
    <div class="edu-explanation"><strong>Giải thích chi tiết</strong><?= nl2br(e($q["explanation"])) ?></div>
</section>
<?php endforeach; ?>
<?php else: ?>
    <?php if (!$questions): ?>
    <div class="edu-empty"><?= eduIcon('file') ?><h2>Chưa có câu hỏi</h2><p>Giáo viên chưa tạo câu hỏi trắc nghiệm cho bài học này.</p></div>
    <?php else: ?>
    <div class="edu-panel"><strong><?= count($questions) ?> câu hỏi</strong><p class="edu-quiz-hint">Mỗi câu chọn một đáp án. Kiểm tra câu trả lời trước khi nộp; bạn chỉ có một lượt làm bài.</p></div>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION["csrf_token"]) ?>">
        <?php foreach ($questions as $index => $q): ?>
        <fieldset class="edu-question">
            <legend>Câu <?= $index + 1 ?>: <?= e($q["question_text"]) ?></legend>
            <?php foreach (["A","B","C","D"] as $letter): ?>
            <label class="edu-answer">
                <input type="radio" name="answers[<?= (int)$q["id"] ?>]" value="<?= $letter ?>" required <?php if (($_POST["answers"][$q["id"]] ?? "") === $letter): ?>checked<?php endif; ?>>
                <span class="edu-answer-letter"><?= $letter ?></span><span><?= e($q["option_" . strtolower($letter)]) ?></span>
            </label>
            <?php endforeach; ?>
        </fieldset>
        <?php endforeach; ?>
        <p><button type="submit" class="btn btn-primary" onclick="return confirm('Nộp bài trắc nghiệm? Sau khi nộp bạn không thể làm lại.');">Nộp bài và xem kết quả <?= eduIcon('arrow') ?></button></p>
    </form>
    <?php endif; ?>
<?php endif; ?>
<p><a href="lesson.php?lesson_id=<?= $lessonId ?>">← Quay lại bài học</a></p>
<?php eduEnd(); ?>
