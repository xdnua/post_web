<?php include __DIR__ . '/../../controllers/BookmarksController.php'; ?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Bài viết đã lưu - Blog Chia Sẻ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet" />
    <link rel="stylesheet" href="<?= $baseUrl ?>/src/styles/global.css" />
</head>

<body style="padding-top: 60px;">
    <?php include __DIR__ . '/../../layout/navbar.php'; ?>

    <div class="container mt-5">
        <h2 class="mb-4 text-center">Bài viết đã lưu của bạn</h2>

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
                                    Lưu bởi <strong><?= $userDisplayName ?></strong> ngày
                                    <?= date('d/m/Y', strtotime($post['created_at'])) ?>
                                </small>
                            </div>
                            <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="me-3"><i class="bi bi-hand-thumbs-up"></i> <?= $post['like_count'] ?></span>
                                    <span><i class="bi bi-hand-thumbs-down"></i> <?= $post['dislike_count'] ?></span>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="post.php?id=<?= $post['id'] ?>" class="btn btn-primary btn-sm">Đọc tiếp</a>
                                    <form method="POST"
                                        onsubmit="return confirm('Bạn có chắc chắn muốn hủy lưu bài viết này?');"
                                        style="display:inline;">
                                        <input type="hidden" name="delete_post_id" value="<?= $post['id'] ?>" />
                                        <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-x-circle"></i>
                                            Xóa</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">Bạn chưa có bài viết đã lưu nào.</div>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../../layout/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>