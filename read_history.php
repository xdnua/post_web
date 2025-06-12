<?php
require_once 'config/database.php'; // Kết nối tới cơ sở dữ liệu
require_once 'auth/auth.php'; // Kiểm tra trạng thái đăng nhập
// Kiểm tra nếu người dùng đã đăng nhập
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$query = "SELECT posts.id, posts.title, read_history.last_read_at 
          FROM read_history 
          JOIN posts ON read_history.post_id = posts.id 
          WHERE read_history.user_id = $user_id 
          ORDER BY read_history.last_read_at DESC";

$result = mysqli_query($conn, $query);
$history = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử đọc bài viết</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="global.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container mt-4">
        <h1 class="mb-4">Lịch sử đọc bài viết</h1>
        <?php if (empty($history)): ?>
            <div class="alert alert-info">Bạn chưa đọc bài viết nào.</div>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($history as $item): ?>
                    <a href="post.php?id=<?= $item['id'] ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1"><?= htmlspecialchars($item['title']) ?></h5>
                            <small class="text-muted">Đọc lần cuối: <?= date('d/m/Y H:i', strtotime($item['last_read_at'])) ?></small>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
