<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/classes.php';
require __DIR__ . '/class_fixtures.php';
$checks = 0;
function check($condition, string $label): void {
    global $checks;
    if (!$condition) throw new RuntimeException('FAIL: ' . $label);
    $checks++;
}
try {
    eduTestFixtures($pdo, false);
    check(!eduClassSchemaReady($pdo), 'pre-migration detected, no unsafe enrollment fallback');
    eduTestMigration($pdo);
    check(eduClassSchemaReady($pdo), 'migration columns available');
    $index = $pdo->query("SHOW INDEX FROM courses WHERE Key_name = 'uq_courses_class_code'")->fetch();
    check($index && (int)$index['Non_unique'] === 0, 'unique class code index');
    $userIdColumn = $pdo->query("SHOW COLUMNS FROM users WHERE Field = 'id'")->fetch();
    $rateIdColumn = $pdo->query("SHOW COLUMNS FROM class_join_attempts WHERE Field = 'student_id'")->fetch();
    check($userIdColumn['Type'] === $rateIdColumn['Type'], 'foreign key types agree');
    eduBackfillClassCodes($pdo);
    $codes = $pdo->query('SELECT id, class_code FROM courses ORDER BY id')->fetchAll(PDO::FETCH_KEY_PAIR);
    check(count($codes) === 4 && count(array_unique($codes)) === 4, 'all old classes receive unique codes');
    foreach ($codes as $code) check(preg_match('/\A[A-F0-9]{16}\z/', $code) === 1, 'code format');
    eduBackfillClassCodes($pdo);
    check($codes === $pdo->query('SELECT id, class_code FROM courses ORDER BY id')->fetchAll(PDO::FETCH_KEY_PAIR), 'backfill is stable');
    try {
        $q = $pdo->prepare('UPDATE courses SET class_code = ? WHERE id = 102');
        $q->execute([$codes[101]]);
        check(false, 'duplicate code rejected');
    } catch (PDOException $e) { check((int)$e->errorInfo[1] === 1062, 'duplicate code rejected'); }
    check(eduJoinClass($pdo, 3, 103, $codes[103])['status'] === 'unavailable', 'pending not joinable');
    check(eduJoinClass($pdo, 3, 104, $codes[104])['status'] === 'unavailable', 'draft not joinable');
    check(eduJoinClass($pdo, 3, 999, 'ABCDEF0123456789')['status'] === 'unavailable', 'unknown class rejected');
    foreach ([null, '', ['bad'], str_repeat('0',16), $codes[102]] as $bad) {
        check(eduJoinClass($pdo, 3, 101, $bad)['status'] === 'invalid_code', 'missing/wrong/array/other class code rejected');
    }
    check((int)$pdo->query('SELECT COUNT(*) FROM enrollments WHERE student_id=3')->fetchColumn() === 0, 'bad code creates no enrollment');
    $result = eduJoinClass($pdo, 3, 101, $codes[101]);
    check($result['status'] === 'throttled' && $result['retry_after'] > 0 && $result['retry_after'] <= 900, 'fifth failure locks account');
    check(eduJoinClass($pdo, 3, 102, $codes[102])['status'] === 'throttled', 'limit spans all classes');
    $pdo->exec('UPDATE class_join_attempts SET window_started = DATE_SUB(NOW(), INTERVAL 16 MINUTE) WHERE student_id=3');
    check(eduJoinClass($pdo, 3, 101, strtolower($codes[101]))['status'] === 'enrolled', 'correct normalized code after cooldown joins');
    check(eduJoinClass($pdo, 3, 101, null)['status'] === 'already', 'existing member needs no code');
    check((int)$pdo->query('SELECT COUNT(*) FROM enrollments WHERE student_id=3 AND course_id=101')->fetchColumn() === 1, 'duplicate POST idempotent');
    check(eduRemoveClassMember($pdo, 2, 101, 3) === 'forbidden', 'another teacher cannot remove member');
    check((int)$pdo->query('SELECT COUNT(*) FROM enrollments WHERE student_id=3 AND course_id=101')->fetchColumn() === 1, 'unauthorized removal changes nothing');
    check(eduRemoveClassMember($pdo, 1, 101, 3) === 'removed', 'owner can remove member');
    check((int)$pdo->query('SELECT COUNT(*) FROM enrollments WHERE student_id=3 AND course_id=101')->fetchColumn() === 0, 'membership revoked');
    check((int)$pdo->query('SELECT COUNT(*) FROM quiz_attempts WHERE student_id=3')->fetchColumn() === 1, 'quiz history retained');
    check((int)$pdo->query('SELECT COUNT(*) FROM lesson_progress WHERE student_id=3')->fetchColumn() === 1, 'progress retained');
    check(eduRemoveClassMember($pdo, 1, 101, 3) === 'absent', 'repeated removal is harmless');
    check(eduJoinClass($pdo, 3, 101, null)['status'] === 'invalid_code', 'removed member cannot bypass code');
    check(eduJoinClass($pdo, 3, 101, $codes[101])['status'] === 'enrolled', 'explicit valid rejoin allowed');
    echo "PASS: $checks membership and SQL checks (temporary tables only).\n";
} finally { $pdo = null; }
