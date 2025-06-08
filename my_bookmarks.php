<?php
require_once 'config/database.php'; // Kết nối DB
require_once 'auth/auth.php'; // Xác thực

$baseUrl = '/posts';
$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    header('Location: /login.php');
    exit;
}

// Lấy thông tin người dùng
$userSql = "SELECT username, first_name, last_name, avatar FROM users WHERE id = ? LIMIT 1";
$userStmt = mysqli_prepare($conn, $userSql);
$loggedInUser = null;
if ($userStmt) {
    mysqli_stmt_bind_param($userStmt, 'i', $user_id);
    mysqli_stmt_execute($userStmt);
    $result = mysqli_stmt_get_result($userStmt);
    $loggedInUser = mysqli_fetch_assoc($result);
    mysqli_stmt_close($userStmt);
}

$userDisplayName = '';
if ($loggedInUser) {
    if (!empty($loggedInUser['first_name']) && !empty($loggedInUser['last_name'])) {
        $userDisplayName = htmlspecialchars($loggedInUser['first_name'] . ' ' . $loggedInUser['last_name']);
    } elseif (!empty($loggedInUser['first_name'])) {
        $userDisplayName = htmlspecialchars($loggedInUser['first_name']);
    } elseif (!empty($loggedInUser['last_name'])) {
        $userDisplayName = htmlspecialchars($loggedInUser['last_name']);
    } else {
        $userDisplayName = htmlspecialchars($loggedInUser['username']);
    }
} else {
    $userDisplayName = 'Người dùng';
}

$userAvatarPath = $baseUrl . '/dist/avatars/' . htmlspecialchars($loggedInUser['avatar'] ?? 'default_avatar.png');

// Xử lý xóa bài viết đã lưu (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post_id'])) {
    $delete_id = (int)$_POST['delete_post_id'];
    $stmt = mysqli_prepare($conn, "DELETE FROM saved_posts WHERE post_id = ? AND user_id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ii', $delete_id, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    header('Location: my_bookmarks.php');
    exit();
}

// Phân trang & tìm kiếm
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$search_term = trim($_GET['search'] ?? '');

$search_condition = " WHERE sp.user_id = ?";
$params = [$user_id];
$param_types = 'i';

if ($search_term !== '') {
    $search_condition .= " AND (p.title LIKE CONCAT('%', ?, '%') OR p.content LIKE CONCAT('%', ?, '%'))";
    $params[] = $search_term;
    $params[] = $search_term;
    $param_types .= 'ss';
}

// Đếm tổng bài viết đã lưu
$count_sql = "SELECT COUNT(*) as total FROM saved_posts sp JOIN posts p ON sp.post_id = p.id " . $search_condition;
$count_stmt = mysqli_prepare($conn, $count_sql);
if ($count_stmt) {
    mysqli_stmt_bind_param($count_stmt, $param_types, ...$params);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $total_posts = mysqli_fetch_assoc($count_result)['total'] ?? 0;
    mysqli_stmt_close($count_stmt);
} else {
    $total_posts = 0;
}

$total_pages = max(1, ceil($total_posts / $limit));

// Lấy danh sách bài viết đã lưu
$list_sql = "SELECT p.*, sp.saved_at,
    (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND type = 'like') AS like_count,
    (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND type = 'dislike') AS dislike_count
    FROM saved_posts sp
    JOIN posts p ON sp.post_id = p.id
    $search_condition
    ORDER BY sp.saved_at DESC
    LIMIT ? OFFSET ?";

$list_stmt = mysqli_prepare($conn, $list_sql);
if ($list_stmt) {
    // Thêm 2 tham số LIMIT, OFFSET
    $full_types = $param_types . 'ii';
    $full_params = array_merge($params, [$limit, $offset]);
    mysqli_stmt_bind_param($list_stmt, $full_types, ...$full_params);
    mysqli_stmt_execute($list_stmt);
    $result = mysqli_stmt_get_result($list_stmt);
} else {
    $result = false;
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Bài viết đã lưu - Blog Chia Sẻ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet" />
    <link rel="stylesheet" href="<?= $baseUrl ?>/global.css" />
</head>
<body style="padding-top: 60px;">
    <?php include 'navbar.php'; ?>

    <div class="container mt-5">
        <h2 class="mb-4 text-center"><i class="bi bi-bookmark-star-fill text-primary"></i> Bài viết đã lưu của bạn</h2>

        <form method="GET" action="" class="mb-4">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Tìm kiếm bài viết đã lưu..." value="<?= htmlspecialchars($search_term) ?>" />
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Tìm kiếm</button>
                <?php if ($search_term !== ''): ?>
                    <a href="my_bookmarks.php" class="btn btn-secondary">Hủy tìm kiếm</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <div class="row g-4">
                <?php while ($post = mysqli_fetch_assoc($result)): ?>
                    <div class="col-md-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($post['title']) ?></h5>
                                <p class="card-text">
                                    <?php
                                        $summary = mb_substr(strip_tags($post['content']), 0, 100);
                                        echo htmlspecialchars($summary . (mb_strlen(strip_tags($post['content'])) > 100 ? '...' : ''));
                                    ?>
                                </p>
                                <small class="text-muted">
                                    Lưu bởi <strong><?= $userDisplayName ?></strong> ngày <?= date('d/m/Y', strtotime($post['saved_at'])) ?>
                                </small>
                            </div>
                            <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="me-3"><i class="bi bi-hand-thumbs-up"></i> <?= $post['like_count'] ?></span>
                                    <span><i class="bi bi-hand-thumbs-down"></i> <?= $post['dislike_count'] ?></span>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="post.php?id=<?= $post['id'] ?>" class="btn btn-primary btn-sm">Đọc tiếp</a>
                                    <form method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn hủy lưu bài viết này?');" style="display:inline;">
                                        <input type="hidden" name="delete_post_id" value="<?= $post['id'] ?>" />
                                        <button type="submit" class="btn btn-danger btn-sm">Hủy lưu</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Phân trang -->
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                        <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $p ?><?= $search_term ? '&search=' . urlencode($search_term) : '' ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php else: ?>
            <div class="alert alert-info text-center">Bạn chưa có bài viết đã lưu nào.</div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
