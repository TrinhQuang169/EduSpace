<?php
require_once __DIR__ . '/ui.php';

// All metrics are read from the existing schema, scoped to the signed-in role.
function eduDashboard(PDO $pdo): void {
    $role = $_SESSION['user_role'];
    $userId = (int)$_SESSION['user_id'];
    $scalar = static function (string $sql, array $params = []) use ($pdo) {
        $query = $pdo->prepare($sql);
        $query->execute($params);
        return $query->fetchColumn();
    };
    if ($role === 'admin') {
        $metrics = [
            [$scalar('SELECT COUNT(*) FROM users'), 'Tổng tài khoản', 'users', 'blue'],
            [$scalar('SELECT COUNT(*) FROM courses'), 'Tổng lớp học', 'book', 'purple'],
            [$scalar("SELECT COUNT(*) FROM courses WHERE status = 'pending'"), 'Lớp học chờ duyệt', 'clock', 'orange'],
            [$scalar('SELECT COUNT(*) FROM enrollments'), 'Lượt tham gia lớp', 'award', 'green'],
        ];
        $intro = 'Theo dõi hoạt động, quản lý nội dung và cùng xây dựng một không gian học tập chất lượng.';
        $cta = ['../admin/courses.php', 'Quản lý lớp học'];
        $actions = [
            ['../admin/courses.php', 'book', 'purple', 'Phê duyệt lớp học', 'Xem nội dung và xử lý các lớp học giáo viên gửi duyệt.'],
            ['../admin/users.php', 'users', 'blue', 'Quản lý tài khoản', 'Tra cứu học sinh, giáo viên và trạng thái tài khoản.'],
            ['../admin/stats.php', 'chart', 'green', 'Thống kê hệ thống', 'Theo dõi số liệu học tập và kết quả trắc nghiệm.'],
        ];
        $query = $pdo->query("SELECT id, title, category, teacher_name, status FROM courses ORDER BY CASE WHEN status = 'pending' THEN 0 ELSE 1 END, id DESC LIMIT 5");
        $recentTitle = 'Lớp học cần quan tâm';
        $listLink = '../admin/courses.php';
    } elseif ($role === 'teacher') {
        $metrics = [
            [$scalar('SELECT COUNT(*) FROM courses WHERE teacher_id = ?', [$userId]), 'Lớp học của tôi', 'book', 'blue'],
            [$scalar("SELECT COUNT(*) FROM courses WHERE teacher_id = ? AND status = 'approved'", [$userId]), 'Lớp học đã duyệt', 'check', 'green'],
            [$scalar('SELECT COUNT(*) FROM lessons l JOIN courses c ON c.id = l.course_id WHERE c.teacher_id = ?', [$userId]), 'Bài học đã tạo', 'file', 'purple'],
            [$scalar('SELECT COUNT(DISTINCT e.student_id) FROM enrollments e JOIN courses c ON c.id = e.course_id WHERE c.teacher_id = ?', [$userId]), 'Học sinh tham gia', 'users', 'orange'],
        ];
        $intro = 'Biến kiến thức thành những bài học giá trị. Quản lý lớp học và đồng hành cùng học sinh của bạn.';
        $cta = ['../teacher/create_course.php', 'Tạo lớp học mới'];
        $actions = [
            ['../teacher/courses.php', 'book', 'purple', 'Nội dung giảng dạy', 'Quản lý bài học, tài liệu và câu hỏi trong từng lớp học.'],
            ['../teacher/create_course.php', 'plus', 'blue', 'Tạo lớp học mới', 'Bắt đầu chia sẻ kiến thức với một lớp học của riêng bạn.'],
            ['../teacher/progress.php', 'chart', 'green', 'Tiến độ & kết quả', 'Theo dõi quá trình học và điểm trắc nghiệm của học sinh.'],
        ];
        $query = $pdo->prepare('SELECT id, title, category, teacher_name, status FROM courses WHERE teacher_id = ? ORDER BY id DESC LIMIT 5');
        $query->execute([$userId]);
        $recentTitle = 'Lớp học gần đây';
        $listLink = '../teacher/courses.php';
    } else {
        $scope = " JOIN lessons l ON l.id = p.lesson_id JOIN courses c ON c.id = l.course_id JOIN enrollments e ON e.course_id = c.id AND e.student_id = p.student_id WHERE p.student_id = ? AND c.status = 'approved'";
        $quizScope = " JOIN quizzes q ON q.id = a.quiz_id JOIN lessons l ON l.id = q.lesson_id JOIN courses c ON c.id = l.course_id JOIN enrollments e ON e.course_id = c.id AND e.student_id = a.student_id WHERE a.student_id = ? AND c.status = 'approved'";
        $average = $scalar('SELECT ROUND(AVG(a.score), 2) FROM quiz_attempts a' . $quizScope, [$userId]);
        $metrics = [
            [$scalar("SELECT COUNT(*) FROM enrollments e JOIN courses c ON c.id = e.course_id WHERE e.student_id = ? AND c.status = 'approved'", [$userId]), 'Lớp học đã tham gia', 'book', 'blue'],
            [$scalar('SELECT COUNT(*) FROM lesson_progress p' . $scope, [$userId]), 'Bài học hoàn thành', 'check', 'green'],
            [$scalar('SELECT COUNT(*) FROM quiz_attempts a' . $quizScope, [$userId]), 'Bài trắc nghiệm đã làm', 'file', 'purple'],
            [$average === null ? '—' : $average . '/10', 'Điểm trung bình', 'award', 'orange'],
        ];
        $intro = 'Mỗi ngày học thêm một điều mới. Tiếp tục hành trình của bạn và khám phá những kiến thức thú vị.';
        $cta = ['../student/my_courses.php', 'Đến lớp học của tôi'];
        $actions = [
            ['../student/courses.php', 'book', 'blue', 'Khám phá lớp học', 'Tìm lớp học phù hợp và bắt đầu hành trình học tập mới.'],
            ['../student/my_courses.php', 'award', 'purple', 'Tiếp tục học tập', 'Truy cập nội dung bài học và tài liệu đã đăng ký.'],
            ['../student/my_courses.php#course-list', 'chart', 'green', 'Tiến độ học tập', 'Chọn lớp học để xem tiến độ và kết quả của bạn.'],
        ];
        $query = $pdo->prepare("SELECT c.id, c.title, c.category, c.teacher_name, c.status FROM enrollments e JOIN courses c ON c.id = e.course_id WHERE e.student_id = ? AND c.status = 'approved' ORDER BY e.enrolled_at DESC, e.id DESC LIMIT 5");
        $query->execute([$userId]);
        $recentTitle = 'Tiếp tục hành trình học tập';
        $listLink = '../student/my_courses.php';
    }
    $recent = $query->fetchAll(PDO::FETCH_ASSOC);
    eduStart('Tổng quan', 'dashboard');
    ?>
    <section class="edu-welcome">
        <div><span class="edu-eyebrow">CÙNG EDUSPACE, TIẾN XA HƠN</span><h1>Xin chào, <?= eduEscape($_SESSION['user_name']) ?>!</h1><p><?= eduEscape($intro) ?></p><a class="btn btn-primary" href="<?= eduEscape($cta[0]) ?>"><?= eduEscape($cta[1]) ?><?= eduIcon('arrow') ?></a></div>
        <div class="edu-welcome-art edu-learning-art" aria-hidden="true"><?= eduLearningArtwork() ?><span class="edu-art-tag edu-art-tag--one"><?= eduIcon('file') ?>Kho tri thức</span><span class="edu-art-tag edu-art-tag--two"><?= eduIcon('book') ?>Học mỗi ngày</span></div>
    </section>
    <div class="edu-section-heading"><h2>Tổng quan hoạt động</h2><span class="edu-badge edu-badge--info">Dữ liệu hiện tại</span></div>
    <div class="edu-stats"><?php foreach ($metrics as $metric) { eduStat(...$metric); } ?></div>
    <div class="edu-section-heading"><h2>Truy cập nhanh</h2></div>
    <div class="edu-action-grid">
        <?php foreach ($actions as $action): ?>
        <a class="edu-action-card" href="<?= eduEscape($action[0]) ?>"><span class="edu-stat-icon edu-tone-<?= eduEscape($action[2]) ?>"><?= eduIcon($action[1]) ?></span><h3><?= eduEscape($action[3]) ?></h3><p><?= eduEscape($action[4]) ?></p><?= eduIcon('arrow') ?></a>
        <?php endforeach; ?>
    </div>
    <div class="edu-section-heading"><h2><?= eduEscape($recentTitle) ?></h2><a href="<?= eduEscape($listLink) ?>">Xem tất cả →</a></div>
    <?php if (!$recent): ?>
        <div class="edu-empty"><?= eduIcon('book') ?><h2>Hành trình đang chờ bạn</h2><p>Chưa có lớp học để hiển thị tại đây.</p><a class="btn btn-secondary" href="<?= eduEscape($role === 'student' ? '../student/courses.php' : $cta[0]) ?>"><?= $role === 'student' ? 'Khám phá lớp học' : eduEscape($cta[1]) ?></a></div>
    <?php else: ?>
    <div class="edu-table-wrap" role="region" aria-label="Lớp học gần đây" tabindex="0"><table class="data-table"><thead><tr><th>Lớp học</th><th>Danh mục</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody>
    <?php foreach ($recent as $item): ?>
        <tr><td><strong><?= eduEscape($item['title']) ?></strong><br><small><?= eduEscape($item['teacher_name'] ?? '') ?></small></td><td><?= eduEscape($item['category']) ?></td><td><?= eduBadge($role === 'student' ? 'enrolled' : $item['status']) ?></td><td><a class="btn btn-primary" href="<?= $role === 'admin' ? '../admin/course_preview.php?course_id=' : ($role === 'teacher' ? '../teacher/lessons.php?course_id=' : '../student/course.php?course_id=') ?><?= (int)$item['id'] ?>"><?= $role === 'admin' ? 'Xem nội dung' : ($role === 'teacher' ? 'Quản lý bài học' : 'Vào lớp học') ?><?= eduIcon('arrow') ?></a></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
    <?php endif;
    eduEnd();
}
