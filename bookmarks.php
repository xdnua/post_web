<?php
require_once 'config/database.php'; // Kết nối CSDL
require_once 'auth/auth.php'; // Kiểm tra đăng nhập
requireLogin(); // Bắt buộc đăng nhập

$baseUrl = '/posts';
$user_id = $_SESSION['user_id'];

// Lấy danh sách bài viết đã lưu (bookmarks) của user hiện tại
$sql = "SELECT b.created_at as bookmarked_at, p.*, u.username, u.first_name, u.last_name, u.avatar
        FROM bookmarks b
        JOIN posts p ON b.post_id = p.id
        JOIN users u ON p.user_id = u.id
        WHERE b.user_id = $user_id
        ORDER BY b.created_at DESC";
$result = mysqli_query($conn, $sql);

// Xử lý xóa bookmark nếu người dùng gửi form xóa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_bookmark_id'])) {
    $delete_id = (int)$_POST['delete_bookmark_id'];
    // Đảm bảo chỉ xóa bookmark của chính user đang đăng nhập
    mysqli_query($conn, "DELETE FROM bookmarks WHERE user_id = $user_id AND post_id = $delete_id");
    // Reload lại trang để cập nhật giao diện
    header('Location: bookmarks.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bài viết đã lưu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?=$baseUrl?>/global.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container mt-4">
    <h2 class="mb-4">Bài viết đã lưu</h2>
    <?php if (mysqli_num_rows($result) == 0): ?>
        <div class="alert alert-info gradient-bg text-light">Bạn chưa lưu bài viết nào.</div>
    <?php else: ?>
        <div class="row">
            <?php while ($post = mysqli_fetch_assoc($result)): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($post['title']); ?></h5>
                            <p class="card-text">
                                <?php
                                $summary = mb_substr(strip_tags($post['content']), 0, 100);
                                if (mb_strlen(strip_tags($post['content'])) > 100) $summary .= '...';
                                echo htmlspecialchars($summary);
                                ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted d-flex align-items-center">
                                    <?php
                                    $authorDisplayName = htmlspecialchars($post['username']);
                                    if (!empty($post['first_name']) && !empty($post['last_name'])) {
                                        $authorDisplayName = htmlspecialchars($post['first_name']) . ' ' . htmlspecialchars($post['last_name']);
                                    } else if (!empty($post['first_name'])) {
                                        $authorDisplayName = htmlspecialchars($post['first_name']);
                                    } else if (!empty($post['last_name'])) {
                                        $authorDisplayName = htmlspecialchars($post['last_name']);
                                    }
                                    $authorAvatarPath = $baseUrl . '/dist/avatars/' . htmlspecialchars($post['avatar'] ?? 'default_avatar.png');
                                    ?>
                                    <img src="<?=$authorAvatarPath?>" alt="Avatar" class="rounded-circle me-1" style="width: 20px; height: 20px; object-fit: cover;">
                                    <?=$authorDisplayName?>
                                </small>
                                <small class="text-muted"><?php echo date('d/m/Y', strtotime($post['created_at'])); ?></small>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
                            <div>
                                <span class="me-2"><i class="bi bi-hand-thumbs-up"></i> <?php echo $post['like_count'] ?? 0; ?></span>
                                <span><i class="bi bi-hand-thumbs-down"></i> <?php echo $post['dislike_count'] ?? 0; ?></span>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="post.php?id=<?php echo $post['id']; ?>" class="btn btn-primary btn-sm">Đọc tiếp</a>
                                <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa bài viết này khỏi danh sách đã lưu?');">
                                    <input type="hidden" name="delete_bookmark_id" value="<?php echo $post['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-x-circle"></i> Xóa</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php include 'footer.php'; ?>
