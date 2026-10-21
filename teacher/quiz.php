
<?php
require_once __DIR__ . "/../includes/auth.php";
requireRole("teacher");

$lessonId = filter_input(
    INPUT_GET, "lesson_id", FILTER_VALIDATE_INT
);

if (!$lessonId || $lessonId < 1) {
    http_response_code(400);
    exit("ID bài học không hợp lệ.");
}

$teacherId = (int)$_SESSION["user_id"];

$stmt = $pdo->prepare(
    "SELECT l.id, l.title AS lesson_title,
            l.course_id, c.title AS course_title,
            c.status
     FROM lessons l
     JOIN courses c ON c.id = l.course_id
     WHERE l.id = ? AND c.teacher_id = ?
     LIMIT 1"
);
$stmt->execute([$lessonId, $teacherId]);
$lesson = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lesson) {
    http_response_code(404);
    exit("Không tìm thấy bài học của bạn.");
}

$courseId = (int)$lesson["course_id"];

$canEdit = in_array(
    $lesson["status"], ["draft", "rejected"], true
);

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars(
        (string)$value, ENT_QUOTES, "UTF-8"
    );
}

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

    if (!$canEdit) {
        http_response_code(403);
        exit("Lớp học không được phép chỉnh sửa.");
    }

    $action = $_POST["action"] ?? "";

    if (!in_array(
        $action,
        ["create_quiz", "add_question", "delete_question"],
        true
    )) {
        http_response_code(400);
        exit("Thao tác không hợp lệ.");
    }

    $title = trim((string)($_POST["title"] ?? ""));

    $questionText = trim(
        (string)($_POST["question_text"] ?? "")
    );

    $options = [];
    foreach (["A", "B", "C", "D"] as $letter) {
        $options[$letter] = trim(
            (string)($_POST["option_" . strtolower($letter)] ?? "")
        );
    }

    $correctAnswer = $_POST["correct_answer"] ?? "";
    $explanation = trim(
        (string)($_POST["explanation"] ?? "")
    );

    if (
        $action === "create_quiz" &&
        ($title === "" || mb_strlen($title) > 255)
    ) {
        $error = "Tên bài trắc nghiệm không hợp lệ.";
    }

    if ($action === "add_question") {
        if (
            $questionText === "" ||
            in_array("", $options, true) ||
            !in_array($correctAnswer, ["A", "B", "C", "D"], true) ||
            $explanation === ""
        ) {
            $error = "Nhập đầy đủ câu hỏi, bốn đáp án và giải thích.";
        }
    }

    $questionId = filter_input(
        INPUT_POST, "question_id", FILTER_VALIDATE_INT
    );

    if ($action === "delete_question" && !$questionId) {
        $error = "ID câu hỏi không hợp lệ.";
    }

    if ($error === "") {
        try {
            $pdo->beginTransaction();

            // Mọi thao tác sửa phải khóa bản ghi lớp học
            $lock = $pdo->prepare(
                "SELECT status FROM courses
                 WHERE id = ? AND teacher_id = ?
                 FOR UPDATE"
            );
            $lock->execute([$courseId, $teacherId]);
            $status = $lock->fetchColumn();

            if (!in_array(
                $status, ["draft", "rejected"], true
            )) {
                throw new RuntimeException(
                    "Lớp học đã thay đổi trạng thái."
                );
            }

            $stmt = $pdo->prepare(
                "SELECT id FROM quizzes
                 WHERE lesson_id = ?"
            );
            $stmt->execute([$lessonId]);
            $quizId = (int)$stmt->fetchColumn();

            if ($action === "create_quiz") {
                if ($quizId) {
                    $stmt = $pdo->prepare(
                        "UPDATE quizzes SET title = ?
                         WHERE id = ?"
                    );
                    $stmt->execute([$title, $quizId]);
                } else {
                    $stmt = $pdo->prepare(
                        "INSERT INTO quizzes (lesson_id, title)
                         VALUES (?, ?)"
                    );
                    $stmt->execute([$lessonId, $title]);
                }
            }

            if ($action === "add_question") {
                if (!$quizId) {
                    throw new RuntimeException(
                        "Bạn chưa tạo bài trắc nghiệm."
                    );
                }

                $stmt = $pdo->prepare(
                    "SELECT COALESCE(MAX(question_order), 0) + 1
                     FROM quiz_questions
                     WHERE quiz_id = ?"
                );
                $stmt->execute([$quizId]);
                $nextOrder = (int)$stmt->fetchColumn();

                $stmt = $pdo->prepare(
                    "INSERT INTO quiz_questions
                     (quiz_id, question_text,
                      option_a, option_b,
                      option_c, option_d,
                      correct_answer, explanation,
                      question_order)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );

                $stmt->execute([
                    $quizId,
                    $questionText,
                    $options["A"],
                    $options["B"],
                    $options["C"],
                    $options["D"],
                    $correctAnswer,
                    $explanation,
                    $nextOrder
                ]);
            }

            if ($action === "delete_question") {
                if (!$quizId) {
                    throw new RuntimeException(
                        "Không tìm thấy bài trắc nghiệm."
                    );
                }

                $stmt = $pdo->prepare(
                    "DELETE FROM quiz_questions
                     WHERE id = ? AND quiz_id = ?"
                );
                $stmt->execute([$questionId, $quizId]);

                if ($stmt->rowCount() !== 1) {
                    throw new RuntimeException(
                        "Không tìm thấy câu hỏi."
                    );
                }
            }

            $pdo->commit();

            header(
                "Location: quiz.php?lesson_id=" . $lessonId
            );
            exit;

        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($ex->getMessage());
            $error = "Không thể lưu dữ liệu trắc nghiệm.";
        }
    }
}

// Lấy bài trắc nghiệm hiện tại
$stmt = $pdo->prepare(
    "SELECT * FROM quizzes WHERE lesson_id = ?"
);
$stmt->execute([$lessonId]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

$questions = [];
$attempts = [];

if ($quiz) {
    $quizId = (int)$quiz["id"];

    $stmt = $pdo->prepare(
        "SELECT * FROM quiz_questions
         WHERE quiz_id = ?
         ORDER BY question_order ASC, id ASC"
    );
    $stmt->execute([$quizId]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare(
        "SELECT student_id, score, correct_count,
                total_questions, submitted_at
         FROM quiz_attempts
         WHERE quiz_id = ?
         ORDER BY submitted_at DESC, id DESC"
    );
    $stmt->execute([$quizId]);
    $attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<?php require_once __DIR__ . "/../includes/ui.php"; eduStart('Quản lý trắc nghiệm', 'courses'); ?>



<h1>Quản lý trắc nghiệm</h1>
<p>Lớp học: <?= e($lesson["course_title"]) ?></p>
<p>Bài học: <?= e($lesson["lesson_title"]) ?></p>

<?php if ($error): ?>
<p class="alert alert-error" role="alert"><?= e($error) ?></p>
<?php endif; ?>

<?php if ($canEdit): ?>
<h2><?= $quiz ? "Đổi tên bài trắc nghiệm" : "Tạo bài trắc nghiệm" ?></h2>

<form method="POST" class="edu-form-card">
<input type="hidden" name="csrf_token"
 value="<?= e($_SESSION["csrf_token"]) ?>">
<input type="hidden" name="action" value="create_quiz">

<label for="quiz-title">Tên bài trắc nghiệm</label><input id="quiz-title" type="text" name="title" maxlength="255" required
 value="<?= e($quiz["title"] ?? "") ?>">

<button type="submit" class="btn btn-primary">
 Lưu bài trắc nghiệm
</button>
</form>
<?php endif; ?>

<?php if ($quiz): ?>

<h2><?= e($quiz["title"]) ?></h2>
<p>Số câu hỏi: <?= count($questions) ?></p>

<?php foreach ($questions as $index => $q): ?><section class="edu-question">

<h3>
Câu <?= $index + 1 ?>:
<?= e($q["question_text"]) ?>
</h3>

<p class="edu-answer"><span class="edu-answer-letter">A</span><?= e($q["option_a"]) ?></p>
<p class="edu-answer"><span class="edu-answer-letter">B</span><?= e($q["option_b"]) ?></p>
<p class="edu-answer"><span class="edu-answer-letter">C</span><?= e($q["option_c"]) ?></p>
<p class="edu-answer"><span class="edu-answer-letter">D</span><?= e($q["option_d"]) ?></p>

<p><strong>Đáp án đúng:</strong>
<?= e($q["correct_answer"]) ?></p>

<p class="edu-explanation"><strong>Giải thích:</strong>
<?= nl2br(e($q["explanation"])) ?></p>

<?php if ($canEdit): ?>
<form method="POST"
 onsubmit="return confirm('Xóa câu hỏi này?')">
<input type="hidden" name="csrf_token"
 value="<?= e($_SESSION["csrf_token"]) ?>">
<input type="hidden" name="action" value="delete_question">
<input type="hidden" name="question_id"
 value="<?= (int)$q["id"] ?>">
<button type="submit" class="btn btn-danger">Xóa câu hỏi</button>
</form>
<?php endif; ?></section>

<?php endforeach; ?>

<?php if ($canEdit): ?>
<hr>
<h2>Thêm câu hỏi mới</h2>

<form method="POST" class="edu-form-card">
<input type="hidden" name="csrf_token"
 value="<?= e($_SESSION["csrf_token"]) ?>">
<input type="hidden" name="action" value="add_question">

<label for="quiz-question_text">Nội dung câu hỏi</label><textarea id="quiz-question_text" name="question_text" rows="3" required></textarea>

<label for="quiz-option_a">Đáp án A</label><input id="quiz-option_a" type="text" name="option_a" required>

<label for="quiz-option_b">Đáp án B</label><input id="quiz-option_b" type="text" name="option_b" required>

<label for="quiz-option_c">Đáp án C</label><input id="quiz-option_c" type="text" name="option_c" required>

<label for="quiz-option_d">Đáp án D</label><input id="quiz-option_d" type="text" name="option_d" required>

<label for="quiz-correct_answer">Chọn đáp án đúng</label><select id="quiz-correct_answer" name="correct_answer" required>
<option value="A">A</option>
<option value="B">B</option>
<option value="C">C</option>
<option value="D">D</option>
</select>

<label for="quiz-explanation">Giải thích chi tiết</label><textarea id="quiz-explanation" name="explanation" rows="5" required></textarea>

<p>
<button type="submit" class="btn btn-primary">
 Thêm câu hỏi
</button>
</p>
</form>
<?php endif; ?>

<hr>
<h2>Kết quả học sinh</h2>

<div class="edu-table-wrap" role="region" aria-label="Bảng dữ liệu" tabindex="0"><table class="data-table">
<thead>
<tr>
<th>ID học sinh</th>
<th>Số câu đúng</th>
<th>Điểm</th>
<th>Ngày làm</th>
</tr>
</thead>
<tbody>
<?php foreach ($attempts as $a): ?>
<tr>
<td><?= (int)$a["student_id"] ?></td>
<td><?= (int)$a["correct_count"] ?> /
<?= (int)$a["total_questions"] ?></td>
<td><?= e($a["score"]) ?>/10</td>
<td><?= e($a["submitted_at"]) ?></td>
</tr>
<?php endforeach; ?>

<?php if (!$attempts): ?>
<tr><td colspan="4">Chưa có học sinh làm bài.</td></tr>
<?php endif; ?>
</tbody>
</table></div>

<?php endif; ?>

<p style="margin-top:25px">
<a href="lessons.php?course_id=<?= $courseId ?>">
 ← Quay lại danh sách bài học
</a>
</p>

<?php eduEnd(); ?>
