<?php include __DIR__ . '/../../controllers/MyPostController.php'; ?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bài đăng của tôi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>

<body>
    <?php include __DIR__ . '/../../layout/navbar.php'; ?>
    <div class="container mt-4">
        <h2>Bài đăng của tôi</h2>
        <form method="GET" action="" class="mb-4">
            <div class="input-group">
                <input type="text" class="form-control" name="search" placeholder="Tìm kiếm bài viết của bạn..."
                    value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Tìm kiếm</button>
                <?php if (isset($_GET['search'])): ?>
                    <a href="my_posts.php" class="btn btn-secondary">Hủy tìm kiếm</a>
                <?php endif; ?>
            </div>
        </form>
        <div class="row">
            <?php while ($post = mysqli_fetch_assoc($result)): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($post['title']); ?></h5>
                            <p class="card-text"><?php
                            $summary = mb_substr(strip_tags($post['content']), 0, 100);
                            if (mb_strlen(strip_tags($post['content'])) > 100)
                                $summary .= '...';
                            echo htmlspecialchars($summary);
                            ?></p>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted d-flex align-items-center">
                                    <?php if ($loggedInUser): // Kiểm tra đã lấy được thông tin người dùng chưa ?>
                                        <img src="<?= $userAvatarPath ?>" alt="Avatar" class="rounded-circle me-1"
                                            style="width: 20px; height: 20px; object-fit: cover;">
                                    <?php endif; ?>
                                    Bởi <?= $userDisplayName ?>
                                </small>
                                <small
                                    class="text-muted"><?php echo date('d/m/Y', strtotime($post['created_at'])); ?></small>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
                            <div>
                                <span class="me-2"><i class="bi bi-hand-thumbs-up"></i>
                                    <?php echo $post['like_count']; ?></span>
                                <span><i class="bi bi-hand-thumbs-down"></i> <?php echo $post['dislike_count']; ?></span>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="post.php?id=<?php echo $post['id']; ?>" class="btn btn-primary btn-sm">Đọc tiếp</a>
                                <button class="btn btn-danger btn-sm"
                                    onclick="confirmDeletePost(<?php echo $post['id']; ?>)">Xóa</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <?php if (mysqli_num_rows($result) == 0): ?>
            <div class="alert alert-info gradient-bg text-light">Bạn chưa có bài đăng nào.</div>
        <?php endif; ?>
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item<?php if ($page <= 1)
                        echo ' disabled'; ?>">
                        <a class="page-link"
                            href="?page=<?php echo $page - 1; ?><?php echo !empty($search_term) ? '&search=' . htmlspecialchars($search_term) : ''; ?>"
                            tabindex="-1">&laquo;</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item<?php if ($i == $page)
                            echo ' active'; ?>">
                            <a class="page-link"
                                href="?page=<?php echo $i; ?><?php echo !empty($search_term) ? '&search=' . htmlspecialchars($search_term) : ''; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item<?php if ($page >= $total_pages)
                        echo ' disabled'; ?>">
                        <a class="page-link"
                            href="?page=<?php echo $page + 1; ?><?php echo !empty($search_term) ? '&search=' . htmlspecialchars($search_term) : ''; ?>">&raquo;</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
    <!-- Form ẩn để xóa bài viết -->
    <form id="deletePostForm" method="POST" action="" style="display:none;">
        <input type="hidden" name="delete_post_id" id="delete_post_id">
    </form>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDeletePost(postId) {
            if (confirm('Bạn có chắc chắn muốn xóa bài viết này?')) {
                document.getElementById('delete_post_id').value = postId;
                document.getElementById('deletePostForm').submit();
            }
        }
    </script>
    <?php
    // Xử lý xóa bài viết
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post_id'])) {
        $delete_id = (int) $_POST['delete_post_id'];
        $user_id = $_SESSION['user_id'];
        $query = "SELECT * FROM posts WHERE id = $delete_id AND user_id = $user_id";
        $result = mysqli_query($conn, $query);
        $post = mysqli_fetch_assoc($result);
        if ($post) {
            // Xóa ảnh trong nội dung
            $imgs = [];
            if (preg_match_all('/src=\"(.*?)\"/', $post['content'], $matches)) {
                $imgs = $matches[1];
            }
            foreach ($imgs as $img_url) {
                $img_path = $_SERVER['DOCUMENT_ROOT'] . parse_url($img_url, PHP_URL_PATH);
                if (strpos($img_path, '/uploads/') !== false && file_exists($img_path)) {
                    @unlink($img_path);
                }
            }
            // Xóa bài viết
            mysqli_query($conn, "DELETE FROM posts WHERE id = $delete_id");
            header('Location: my_posts.php');
            exit();
        }
    }
    ?>
</body>

</html>
<?php include __DIR__ . '/../../layout/footer.php'; ?>