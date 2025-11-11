<?php
require_once 'config/database.php';
require_once 'auth/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy danh sách thông báo
$query = "SELECT id, message, is_read, created_at, link, post_id FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$notifications = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Đánh dấu thông báo là đã đọc
$updateQuery = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
$stmt = mysqli_prepare($conn, $updateQuery);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông báo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="global.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <h1 class="text-center mb-4">Thông báo</h1>
    <div class="list-group">
        <?php if (empty($notifications)): ?>
            <div class="alert alert-info text-center">Không có thông báo nào.</div>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <?php
                // Lấy tiêu đề bài viết từ cơ sở dữ liệu nếu có post_id
                $postTitle = '';
                if (!empty($notification['post_id'])) {
                    $postQuery = "SELECT title FROM posts WHERE id = ?";
                    $postStmt = mysqli_prepare($conn, $postQuery);
                    mysqli_stmt_bind_param($postStmt, 'i', $notification['post_id']);
                    mysqli_stmt_execute($postStmt);
                    $postResult = mysqli_stmt_get_result($postStmt);
                    $post = mysqli_fetch_assoc($postResult);
                    $postTitle = $post['title'] ?? '';
                    mysqli_stmt_close($postStmt);
                }

                $link = !empty($notification['link']) && filter_var($notification['link'], FILTER_VALIDATE_URL)
                    ? $notification['link']
                    : (!empty($notification['post_id']) ? 'post.php?id=' . $notification['post_id'] : 'index.php');
                ?>
                <div class="list-group-item <?= $notification['is_read'] ? '' : 'list-group-item-warning' ?>">
                    <a href="<?= htmlspecialchars($link) ?>" class="text-decoration-none">
                        <p class="mb-1 text-dark">
                            <?= strpos($notification['message'], 'bình luận') !== false
                                ? 'Ai đó đã bình luận bài viết: ' . htmlspecialchars($postTitle)
                                : 'Ai đó đã phản hồi bài viết: ' . htmlspecialchars($postTitle) ?>
                        </p>
                    </a>
                    <small class="text-muted"><?= $notification['created_at'] ?></small>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


