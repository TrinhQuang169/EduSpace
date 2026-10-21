

<?php
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/artwork.php";

$sql = "SELECT id, title, category, icon, color,
               students, teacher_name
        FROM courses
        WHERE status = 'approved'
        ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$courses = $stmt->fetchAll();
?>


<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>EduSpace - Học tập trực tuyến</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/effects.css?v=20261014-2">
    <script src="assets/js/effects.js?v=20261014-2" defer></script>
</head>
<body class="edu-public">

<header class="header">
    <div class="container nav">
        <a href="index.php" class="logo">
            🎓 EduSpace
        </a>

        <nav class="nav-links">
            <a href="index.php">Trang chủ</a>
            <a href="#courses">Lớp học</a>
            <a href="#features">Giới thiệu</a>
        </nav>

        <div class="nav-actions">
            <?php eduMotionControl(); ?>
            <a href="auth/login.php" class="login-link">
    Đăng nhập
</a>
            <a href="auth/register.php" class="btn btn-primary">
    Đăng ký
</a>
        </div>
    </div>
</header>

<main>
    <section class="hero">
        <div class="container hero-content">
            <div class="hero-text">
                <span class="eyebrow">
                    NỀN TẢNG HỌC TẬP TRỰC TUYẾN
                </span>

                <h1>
                    Khám phá tri thức,
                    <span>phát triển tương lai.</span>
                </h1>

                <p>
                    Học tập mọi lúc, mọi nơi cùng EduSpace.
                    Khám phá các lớp học chất lượng,
                    theo dõi tiến độ và nâng cao kỹ năng.
                </p>

                <a href="#courses" class="btn btn-primary">
                    Khám phá lớp học →
                </a>
            </div>

            <div class="hero-visual">
                <div class="edu-learning-art" aria-hidden="true"><?= eduLearningArtwork() ?><span class="edu-art-tag edu-art-tag--one">✦ Khám phá tri thức</span><span class="edu-art-tag edu-art-tag--two">✧ Nuôi dưỡng đam mê</span></div>
                <h2>Học tập không giới hạn</h2>
                <p>Kiến thức trong tầm tay bạn</p>
            </div>
        </div>
    </section>

    <section class="search-section">
        <div class="container">
            <div class="search-box">
                <input
                    type="text"
                    id="searchInput"
                    placeholder="Tìm kiếm lớp học..."
                >
                <button id="searchBtn" class="btn btn-primary">
                    Tìm kiếm
                </button>
            </div>
        </div>
    </section>

    <section id="courses" class="courses-section">
        <div class="container">
            <div class="section-heading">
                <div>
                    <span class="section-label">
                        KHÁM PHÁ LỚP HỌC
                    </span>
                    <h2>Lớp học nổi bật</h2>
                    <p>
                        Bắt đầu hành trình học tập
                        với các lớp học phù hợp.
                    </p>
                </div>
            </div>

            <div class="course-grid" id="courseGrid">
                <?php foreach ($courses as $course): ?>
                    <article class="course-card">
                        <div class="course-image
                            <?php echo $course['color']; ?>">
                            <span>
                                <?php echo $course['icon']; ?>
                            </span>
                        </div>

                        <div class="course-body">
                            <span class="category">
                                <?php echo htmlspecialchars(
                                    $course['category']
                                ); ?>
                            </span>

                            <h3>
                                <?php echo htmlspecialchars(
                                    $course['title']
                                ); ?>
                            </h3>

                            <p class="teacher">
                                👨‍🏫
                                <?php echo htmlspecialchars(
                                    $course['teacher_name']
                                ); ?>
                            </p>

                            <div class="course-footer">
                                <span>
                                    👥 <?php echo $course['students']; ?>
                                    học sinh
                                </span>
                                <span class="free">Miễn phí</span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <p id="noResults" class="no-results" hidden>
                Không tìm thấy lớp học phù hợp.
            </p>
        </div>
    </section>

    <section id="features" class="features-section">
        <div class="container">
            <div class="section-title-center">
                <span class="section-label">
                    TẠI SAO CHỌN EDUSPACE?
                </span>
                <h2>Học tập dễ dàng và hiệu quả</h2>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📚</div>
                    <h3>Kho học liệu phong phú</h3>
                    <p>
                        Tiếp cận bài giảng và tài liệu
                        trực tuyến thuận tiện.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Theo dõi tiến độ</h3>
                    <p>
                        Quản lý quá trình học tập
                        và kết quả của bản thân.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📝</div>
                    <h3>Kiểm tra kiến thức</h3>
                    <p>
                        Làm bài tập, trắc nghiệm
                        và đánh giá kết quả học tập.
                    </p>
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="footer">
    <div class="container footer-content">
        <div>
            <h3>🎓 EduSpace</h3>
            <p>Nền tảng quản lý và hỗ trợ học tập trực tuyến.</p>
        </div>
        <p>© <?php echo date("Y"); ?> EduSpace.</p>
    </div>
</footer>

<script src="assets/js/main.js"></script>
</body>
</html>
