<?php
// Membership operations only; no implicit schema changes.
const EDU_CLASS_CODE_LENGTH = 16;
const EDU_JOIN_MAX_FAILURES = 5;
const EDU_JOIN_WINDOW_SECONDS = 900;

function eduGenerateClassCode(): string {
    return strtoupper(bin2hex(random_bytes(8)));
}

function eduClassSchemaReady(PDO $pdo): bool {
    try {
        $pdo->query('SELECT class_code FROM courses LIMIT 0');
        $pdo->query('SELECT student_id, failed_attempts, window_started FROM class_join_attempts LIMIT 0');
        return true;
    } catch (PDOException $error) {
        if (in_array((int)($error->errorInfo[1] ?? 0), [1054, 1146], true)) return false;
        throw $error;
    }
}

function eduClassMigrationMessage(): string {
    return 'Chức năng tham gia lớp chưa sẵn sàng. Quản trị viên cần chạy migration database/20261013_class_membership.sql, rồi tải lại danh sách lớp.';
}

function eduBackfillClassCodes(PDO $pdo): void {
    // Compare-and-set preserves assigned codes during concurrent requests.
    $ids = $pdo->query('SELECT id FROM courses WHERE class_code IS NULL')->fetchAll(PDO::FETCH_COLUMN);
    $update = $pdo->prepare('UPDATE courses SET class_code = ? WHERE id = ? AND class_code IS NULL');
    foreach ($ids as $id) {
        for ($retry = 0; $retry < 5; $retry++) {
            try {
                $update->execute([eduGenerateClassCode(), (int)$id]);
                break;
            } catch (PDOException $error) {
                if ((int)($error->errorInfo[1] ?? 0) !== 1062 || $retry === 4) throw $error;
            }
        }
    }
}

function eduPrepareClasses(PDO $pdo): bool {
    if (!eduClassSchemaReady($pdo)) return false;
    eduBackfillClassCodes($pdo);
    return true;
}

function eduJoinClass(PDO $pdo, int $studentId, int $courseId, $suppliedCode): array {
    $pdo->beginTransaction();
    try {
        // Same course-row lock as removal, approval and quiz submission.
        $query = $pdo->prepare('SELECT id, status, class_code FROM courses WHERE id = ? FOR UPDATE');
        $query->execute([$courseId]);
        $course = $query->fetch(PDO::FETCH_ASSOC);
        if (!$course || $course['status'] !== 'approved') {
            $pdo->rollBack();
            return ['status' => 'unavailable'];
        }
        $query = $pdo->prepare('SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?');
        $query->execute([$studentId, $courseId]);
        if ($query->fetchColumn()) {
            $pdo->commit();
            return ['status' => 'already'];
        }
        $query = $pdo->prepare('INSERT INTO class_join_attempts (student_id, failed_attempts, window_started) VALUES (?, 0, NOW()) ON DUPLICATE KEY UPDATE student_id = student_id');
        $query->execute([$studentId]);
        $query = $pdo->prepare('SELECT failed_attempts, TIMESTAMPDIFF(SECOND, window_started, NOW()) AS elapsed FROM class_join_attempts WHERE student_id = ? FOR UPDATE');
        $query->execute([$studentId]);
        $limit = $query->fetch(PDO::FETCH_ASSOC);
        $elapsed = max(0, (int)$limit['elapsed']);
        if ($elapsed >= EDU_JOIN_WINDOW_SECONDS) {
            $query = $pdo->prepare('UPDATE class_join_attempts SET failed_attempts = 0, window_started = NOW() WHERE student_id = ?');
            $query->execute([$studentId]);
            $limit['failed_attempts'] = 0;
            $elapsed = 0;
        }
        if ((int)$limit['failed_attempts'] >= EDU_JOIN_MAX_FAILURES) {
            $pdo->commit();
            return ['status' => 'throttled', 'retry_after' => EDU_JOIN_WINDOW_SECONDS - $elapsed];
        }
        $code = is_string($suppliedCode) ? strtoupper(trim($suppliedCode)) : '';
        if (!preg_match('/\A[A-F0-9]{16}\z/', $code) || !is_string($course['class_code']) || !hash_equals($course['class_code'], $code)) {
            $query = $pdo->prepare('UPDATE class_join_attempts SET failed_attempts = failed_attempts + 1 WHERE student_id = ?');
            $query->execute([$studentId]);
            $pdo->commit();
            return ['status' => 'invalid_code'];
        }
        // Only enrollment insert in the application, after server-side code validation.
        $query = $pdo->prepare('INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)');
        $query->execute([$studentId, $courseId]);
        // Success does not reset failures: a known code cannot reset the guessing budget.
        $pdo->commit();
        return ['status' => 'enrolled'];
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}

function eduRemoveClassMember(PDO $pdo, int $teacherId, int $courseId, int $studentId): string {
    $pdo->beginTransaction();
    try {
        $query = $pdo->prepare('SELECT id FROM courses WHERE id = ? AND teacher_id = ? FOR UPDATE');
        $query->execute([$courseId, $teacherId]);
        if (!$query->fetchColumn()) {
            $pdo->rollBack();
            return 'forbidden';
        }
        // Keep learning history. Student endpoints always check the live enrollment.
        $query = $pdo->prepare('DELETE FROM enrollments WHERE course_id = ? AND student_id = ?');
        $query->execute([$courseId, $studentId]);
        $removed = $query->rowCount() === 1;
        $pdo->commit();
        return $removed ? 'removed' : 'absent';
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}
