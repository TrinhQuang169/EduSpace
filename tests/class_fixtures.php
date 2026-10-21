<?php
if (PHP_SAPI !== 'cli' && !defined('EDU_CLASS_TEST_ROUTER')) { http_response_code(404); exit; }

function eduTestFixtures(PDO $pdo, bool $migrated = true): void {
    // Connection-local shadows: every write below targets TEMPORARY tables.
    // No production row/schema is changed; closing PDO drops these shadows.
    foreach (['users','courses','enrollments','lessons','lesson_materials','lesson_progress','quizzes','quiz_questions','quiz_attempts','quiz_answers'] as $table) {
        $ddl = $pdo->query('SHOW CREATE TABLE `' . $table . '`')->fetch(PDO::FETCH_NUM)[1];
        $ddl = preg_replace('/^\s*CONSTRAINT [^\n]+\n/m', '', $ddl);
        $ddl = preg_replace('/,\s*\)/', "\n)", $ddl);
        $ddl = preg_replace('/^CREATE TABLE /', 'CREATE TEMPORARY TABLE ', $ddl);
        $pdo->exec($ddl);
    }
    $columns = $pdo->query('SHOW COLUMNS FROM courses')->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('class_code', $columns, true)) {
        $pdo->exec('ALTER TABLE courses DROP COLUMN class_code'); // Temporary shadow only.
    }
    if ($migrated) eduTestMigration($pdo);
    $pdo->exec("INSERT INTO users (id, full_name, email, password_hash, role, status) VALUES
        (1, 'Giáo viên thử nghiệm', 'teacher1@example.test', 'unused', 'teacher', 'active'),
        (2, 'Giáo viên khác', 'teacher2@example.test', 'unused', 'teacher', 'active'),
        (3, 'Học sinh thử nghiệm', 'student1@example.test', 'unused', 'student', 'active'),
        (4, 'Học sinh đã tham gia', 'student2@example.test', 'unused', 'student', 'active'),
        (5, 'Quản trị thử nghiệm', 'admin@example.test', 'unused', 'admin', 'active')");
    $pdo->exec("INSERT INTO courses (id, teacher_id, title, category, description, teacher_name, status) VALUES
        (101,1,'Lập trình PHP căn bản','Công nghệ','Bài học và bài tập thực hành.','Giáo viên thử nghiệm','approved'),
        (102,2,'Toán học ứng dụng','Toán học','Nội dung lớp thứ hai.','Giáo viên khác','approved'),
        (103,1,'Lớp chờ phê duyệt','Công nghệ','','Giáo viên thử nghiệm','pending'),
        (104,1,'Lớp bản nháp','Công nghệ','','Giáo viên thử nghiệm','draft')");
    if ($migrated) {
        $pdo->exec("UPDATE courses SET class_code = CASE id WHEN 101 THEN 'ABCDEF0123456789' WHEN 102 THEN 'FEDCBA9876543210' ELSE NULL END");
    }
    $pdo->exec("INSERT INTO lessons (id, course_id, title, content, lesson_order) VALUES (201,101,'Bài mở đầu','Nội dung bài học.',1),(202,102,'Bài của lớp khác','Nội dung lớp khác.',1)");
    $pdo->exec("INSERT INTO enrollments (student_id, course_id) VALUES (4,101)");
    $pdo->exec("INSERT INTO lesson_materials (id,lesson_id,file_name,stored_name,file_type,file_size) VALUES (501,201,'Tài liệu.pdf','00000000000000000000000000000000.pdf','pdf',20)");
    $pdo->exec("INSERT INTO quizzes (id,lesson_id,title) VALUES (301,201,'Ôn tập')");
    $pdo->exec("INSERT INTO quiz_questions (id,quiz_id,question_text,option_a,option_b,option_c,option_d,correct_answer,explanation,question_order) VALUES (401,301,'Một cộng một bằng mấy?','Một','Hai','Ba','Bốn','B','Một cộng một bằng hai.',1)");
    // Retained history without membership models a removed student.
    $pdo->exec("INSERT INTO lesson_progress (student_id,lesson_id) VALUES (3,201),(4,201)");
    $pdo->exec("INSERT INTO quiz_attempts (id,quiz_id,student_id,correct_count,total_questions,score) VALUES (601,301,3,1,1,10),(602,301,4,1,1,10)");
    $pdo->exec("INSERT INTO quiz_answers (attempt_id,question_id,selected_answer,is_correct) VALUES (601,401,'B',1),(602,401,'B',1)");
}

function eduTestMigration(PDO $pdo): void {
    $sql = file_get_contents(__DIR__ . '/../database/20261013_class_membership.sql');
    // TEMPORARY tables are absent from information_schema. Force the add branch;
    // the actual ALTER/UNIQUE statements are executed against the local shadow.
    $sql = preg_replace('/SET @edu_has_code = .*?;/s', 'SET @edu_has_code = 0;', $sql);
    $sql = preg_replace('/SET @edu_has_index = .*?;/s', 'SET @edu_has_index = 0;', $sql);
    $sql = str_replace('CREATE TABLE IF NOT EXISTS class_join_attempts', 'CREATE TEMPORARY TABLE class_join_attempts', $sql);
    // InnoDB TEMPORARY tables do not support foreign keys. Validate the real
    // FK's referenced type separately; never create a persistent test table.
    $sql = preg_replace('/,\s*CONSTRAINT fk_class_join_student.*?ON DELETE CASCADE/s', '', $sql);
    foreach (explode(';', $sql) as $statement) {
        if (trim(preg_replace('/--[^\n]*/', '', $statement)) === '') continue;
        $query = $pdo->query($statement);
        if ($query) $query->closeCursor();
    }
}
