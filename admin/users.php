
<?php
require_once __DIR__ . "/../includes/auth.php";
requireRole("admin");

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}

$keyword = trim((string)($_GET["search"] ?? ""));
$role = $_GET["role"] ?? "";

$roles = [
    "student" => "Học sinh",
    "teacher" => "Giáo viên",
    "admin" => "Quản trị viên"
];

if (!isset($roles[$role])) {
    $role = "";
}

$sql = "SELECT id, full_name, email, role,
               status, created_at
        FROM users
        WHERE 1=1";

$params = [];

if ($role !== "") {
    $sql .= " AND role = ?";
    $params[] = $role;
}

if ($keyword !== "") {
    $sql .= " AND (
        full_name LIKE ?
        OR email LIKE ?
        OR CAST(id AS CHAR) = ?
    )";

    $params[] = "%" . $keyword . "%";
    $params[] = "%" . $keyword . "%";
    $params[] = $keyword;
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê tổng tài khoản
$stmt = $pdo->query(
    "SELECT role, COUNT(*) AS total
     FROM users
     GROUP BY role"
);

$counts = [
    "student" => 0,
    "teacher" => 0,
    "admin" => 0
];

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if (isset($counts[$row["role"]])) {
        $counts[$row["role"]] = (int)$row["total"];
    }
}

$totalUsers = array_sum($counts);
?>

<?php require_once __DIR__ . "/../includes/ui.php"; eduStart('Quản lý tài khoản', 'users'); ?>



    <h1>Quản lý tài khoản EduSpace</h1>

<p>Gặp gỡ cộng đồng EduSpace. Tra cứu và quản lý quyền truy cập tài khoản.</p>
<div class="edu-stats">
<?php eduStat($totalUsers, 'Tổng tài khoản', 'users');
eduStat($counts['student'], 'Học sinh', 'book', 'purple');
eduStat($counts['teacher'], 'Giáo viên', 'award', 'green');
eduStat($counts['admin'], 'Quản trị viên', 'check', 'orange'); ?>
</div>

    <?php if (
        ($_GET["message"] ?? "") === "updated"
    ): ?>
        <div class="alert alert-success" role="status">Đã cập nhật trạng thái tài khoản.</div>
    <?php endif; ?>

    <form method="GET" class="edu-filter" aria-label="Tìm kiếm tài khoản">
        <input
            type="text"
            name="search" aria-label="Tìm tên, email hoặc ID"
            placeholder="Tìm tên, email hoặc ID"
            value="<?= e($keyword) ?>"
        >

        <select name="role" aria-label="Lọc theo vai trò">
            <option value="">Tất cả vai trò</option>

            <?php foreach ($roles as $key => $label): ?>
                <option
                    value="<?= e($key) ?>"
                    <?= $role === $key ? "selected" : "" ?>
                >
                    <?= e($label) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn btn-primary">
            Tìm kiếm
        </button>

        <a href="users.php">Xóa bộ lọc</a>
    </form>

    <h2 style="margin-top:25px">Danh sách tài khoản</h2>

    <div class="edu-table-wrap" role="region" aria-label="Bảng dữ liệu" tabindex="0"><table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Họ tên</th>
                    <th>Email</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th>Thao tác</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= (int)$user["id"] ?></td>

                        <td><?= e($user["full_name"]) ?></td>

                        <td><?= e($user["email"]) ?></td>

                        <td>
                            <?= e(
                                $roles[$user["role"]]
                                ?? $user["role"]
                            ) ?>
                        </td>

                        <td>
                            <?= eduBadge($user["status"]) ?>
                        </td>

                        <td>
                            <?= e($user["created_at"]) ?>
                        </td>

                        <td>
                            <?php if ($user["role"] === "admin"): ?>

                                Không cho phép thay đổi

                            <?php else: ?>

                                <form
                                    method="POST"
                                    action="toggle_user.php"
                                    style="display:inline-block"
                                    onsubmit="return confirm('Xác nhận thay đổi trạng thái tài khoản?');"
                                >
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e($_SESSION["csrf_token"]) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="user_id"
                                        value="<?= (int)$user["id"] ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                    >
                                        <?= $user["status"] === "active"
                                            ? "Khóa"
                                            : "Mở khóa" ?>
                                    </button>
                                </form>

                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$users): ?>
                    <tr>
                        <td colspan="7">
                            Không tìm thấy tài khoản.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table></div>

    <p style="margin-top:25px">
        <a href="../dashboards/admin.php">
            ← Quay lại Dashboard Admin
        </a>
    </p>

<?php eduEnd(); ?>
