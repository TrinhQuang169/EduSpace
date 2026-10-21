-- Chay trong database online_learning tren phpMyAdmin. Co the chay lai an toan.
-- Khong xoa du lieu. PHP backfill ma bang random_bytes() khi mo danh sach lop.
-- MariaDB 10.4 khong ho tro ham SQL RANDOM_BYTES(). Ma cu khong bi doi.
SET @edu_has_code = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'courses' AND COLUMN_NAME = 'class_code');
SET @edu_ddl = IF(@edu_has_code = 0,
    'ALTER TABLE courses ADD COLUMN class_code CHAR(16) CHARACTER SET ascii COLLATE ascii_bin NULL DEFAULT NULL', 'SELECT 1');
PREPARE edu_migration FROM @edu_ddl;
EXECUTE edu_migration;
DEALLOCATE PREPARE edu_migration;

SET @edu_has_index = (SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'courses' AND INDEX_NAME = 'uq_courses_class_code');
SET @edu_ddl = IF(@edu_has_index = 0,
    'ALTER TABLE courses ADD UNIQUE INDEX uq_courses_class_code (class_code)', 'SELECT 1');
PREPARE edu_migration FROM @edu_ddl;
EXECUTE edu_migration;
DEALLOCATE PREPARE edu_migration;

-- Gioi han thu sai theo tai khoan, khong phu thuoc session/IP.
CREATE TABLE IF NOT EXISTS class_join_attempts (
    student_id INT(11) NOT NULL,
    failed_attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    window_started DATETIME NOT NULL,
    PRIMARY KEY (student_id),
    CONSTRAINT fk_class_join_student FOREIGN KEY (student_id)
        REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
