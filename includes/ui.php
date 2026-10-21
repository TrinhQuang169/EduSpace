<?php
// Presentation helpers only. Pages must run their existing authorization first.
require_once __DIR__ . '/artwork.php';
function eduEscape($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function eduIcon(string $name): string {
    $paths = [
        'book' => '<path d="M12 7v14m0-14C9 4 5 4 2 5v14c3-1 7-1 10 2 3-3 7-3 10-2V5c-3-1-7-1-10 2Z"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
        'users' => '<circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3m1-17a3 3 0 0 1 0 6m2 4a5 5 0 0 1 3 5v2"/>',
        'chart' => '<path d="M4 3v17h17M8 16v-4m5 4V8m5 8V5"/>',
        'check' => '<path d="m7 12 3 3 7-7"/><circle cx="12" cy="12" r="9"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'arrow' => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
        'home' => '<path d="m3 10 9-7 9 7v11H3V10Zm6 11v-8h6v8"/>',
        'logout' => '<path d="M9 4H4v16h5m0-8h12m-4-4 4 4-4 4"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'close' => '<path d="m6 6 12 12M6 18 18 6"/>',
        'award' => '<circle cx="12" cy="8" r="5"/><path d="m8 12-2 9 6-3 6 3-2-9"/>',
        'file' => '<path d="M14 3H5v18h14V8l-5-5Zm0 0v5h5M8 12h8m-8 4h5"/>',
    ];
    return '<svg class="edu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['book']) . '</svg>';
}

function eduBadge($status, ?string $label = null): string {
    $status = (string)$status;
    $states = [
        'draft' => ['neutral', 'Bản nháp'], 'pending' => ['warning', 'Chờ duyệt'],
        'approved' => ['success', 'Đã duyệt'], 'rejected' => ['danger', 'Bị từ chối'],
        'active' => ['success', 'Hoạt động'], 'locked' => ['danger', 'Bị khóa'],
        'complete' => ['success', 'Đã hoàn thành'], 'incomplete' => ['neutral', 'Chưa hoàn thành'],
        'enrolled' => ['success', 'Đã tham gia'], 'available' => ['info', 'Cần mã lớp'],
        'word' => ['info', 'Word'], 'pdf' => ['danger', 'PDF'], 'video' => ['purple', 'Video'],
    ];
    $state = $states[$status] ?? ['neutral', $status];
    return '<span class="edu-badge edu-badge--' . $state[0] . '">' . eduEscape($label ?? $state[1]) . '</span>';
}

function eduStat($value, string $label, string $icon = 'chart', string $tone = 'blue'): void { ?>
    <article class="edu-stat edu-stat--<?= eduEscape($tone) ?>">
        <span class="edu-stat-icon edu-tone-<?= eduEscape($tone) ?>"><?= eduIcon($icon) ?></span>
        <div><p><?= eduEscape($label) ?></p><strong><?= eduEscape($value) ?></strong></div>
    </article>
<?php }

function eduStart(string $title, string $section = 'courses'): void {
    $role = $_SESSION['user_role'] ?? '';
    $roles = ['admin' => 'Quản trị viên', 'teacher' => 'Giáo viên', 'student' => 'Học sinh'];
    $name = $_SESSION['user_name'] ?? '';
    $nav = [
        ['dashboard', '../dashboards/' . $role . '.php', 'grid', 'Tổng quan'],
    ];
    if ($role === 'admin') {
        $nav[] = ['courses', '../admin/courses.php', 'book', 'Quản lý lớp học'];
        $nav[] = ['users', '../admin/users.php', 'users', 'Quản lý tài khoản'];
        $nav[] = ['stats', '../admin/stats.php', 'chart', 'Thống kê hệ thống'];
    } elseif ($role === 'teacher') {
        $nav[] = ['courses', '../teacher/courses.php', 'book', 'Lớp học của tôi'];
        $nav[] = ['create', '../teacher/create_course.php', 'plus', 'Tạo lớp học'];
        $nav[] = ['members', '../teacher/members.php', 'users', 'Thành viên lớp'];
        $nav[] = ['progress', '../teacher/progress.php', 'chart', 'Tiến độ & kết quả'];
    } else {
        $nav[] = ['courses', '../student/courses.php', 'book', 'Khám phá lớp học'];
        $nav[] = ['my-courses', '../student/my_courses.php', 'award', 'Lớp học của tôi'];
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $initial = mb_strtoupper(mb_substr(trim($name), 0, 1, 'UTF-8'), 'UTF-8');
    ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4f46e5">
    <title><?= eduEscape($title) ?> · EduSpace</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/app.css?v=20261014">
    <link rel="stylesheet" href="../assets/css/visuals.css?v=20261014">
    <link rel="stylesheet" href="../assets/css/effects.css?v=20261014-2">
    <script src="../assets/js/app.js" defer></script>
    <script src="../assets/js/effects.js?v=20261014-2" defer></script>
</head>
<body class="edu-app">
<a class="edu-skip" href="#main-content">Chuyển đến nội dung chính</a>
<aside class="edu-sidebar" id="edu-sidebar" aria-label="Điều hướng chính">
    <div class="edu-brand-row">
        <a class="edu-brand" href="../dashboards/<?= eduEscape($role) ?>.php"><span class="edu-brand-mark"><?= eduIcon('book') ?></span>EduSpace<span class="edu-brand-dot">.</span></a>
        <button class="edu-icon-button edu-menu-close" type="button" aria-label="Đóng menu"><?= eduIcon('close') ?></button>
    </div>
    <div class="edu-workspace"><span class="edu-workspace-dot"></span>Không gian <?= eduEscape(mb_strtolower($roles[$role] ?? '', 'UTF-8')) ?></div>
    <p class="edu-nav-label">KHÔNG GIAN LÀM VIỆC</p>
    <nav class="edu-nav">
        <?php foreach ($nav as $item): ?>
            <a href="<?= eduEscape($item[1]) ?>" class="<?= $section === $item[0] ? 'is-active' : '' ?>" <?= $section === $item[0] ? 'aria-current="page"' : '' ?>><?= eduIcon($item[2]) ?><span><?= eduEscape($item[3]) ?></span></a>
        <?php endforeach; ?>
    </nav>
    <div class="edu-sidebar-bottom">
        <div class="edu-sidebar-note"><?= eduIcon('award') ?><strong>Tri thức mở lối tương lai</strong><p>Mỗi bài học là một bước tiến mới.</p></div>
        <a class="edu-home-link" href="../index.php"><?= eduIcon('home') ?>Trang chủ EduSpace</a>
        <form action="../auth/logout.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= eduEscape($_SESSION['csrf_token']) ?>">
            <button type="submit" class="edu-logout"><?= eduIcon('logout') ?>Đăng xuất</button>
        </form>
        <div class="edu-sidebar-user"><span class="edu-avatar"><?= eduEscape($initial) ?></span><div><strong><?= eduEscape($name) ?></strong><small><?= eduEscape($roles[$role] ?? '') ?></small></div></div>
    </div>
</aside>
<button class="edu-backdrop" type="button" aria-label="Đóng menu" hidden></button>
<div class="edu-workarea">
    <header class="edu-topbar">
        <div class="edu-topbar-title"><button type="button" class="edu-icon-button edu-menu-open" aria-label="Mở menu điều hướng" aria-controls="edu-sidebar" aria-expanded="false"><?= eduIcon('menu') ?></button><div><small>EDUSPACE / <?= eduEscape($roles[$role] ?? '') ?></small><p><?= eduEscape($title) ?></p></div></div>
        <div class="edu-topbar-tools"><?php eduMotionControl(); ?><div class="edu-topbar-user"><div><strong><?= eduEscape($name) ?></strong><small><?= eduEscape($roles[$role] ?? '') ?></small></div><span class="edu-avatar"><?= eduEscape($initial) ?></span></div></div>
    </header>
    <main class="edu-content" id="main-content" tabindex="-1">
<?php }

function eduEnd(): void { ?>
    </main>
    <footer class="edu-app-footer"><span>EduSpace · Không gian học tập của bạn</span><span>Học tập. Kết nối. Phát triển.</span></footer>
</div>
</body>
</html>
<?php }
