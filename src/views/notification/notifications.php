<?php include __DIR__ . '/../../controllers/NotificationController.php'; ?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông báo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $baseUrl ?>/src/styles/global.css">
</head>

<body>
    <?php include __DIR__ . '/../../layout/navbar.php'; ?>

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

    <?php include __DIR__ . '/../../layout/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>