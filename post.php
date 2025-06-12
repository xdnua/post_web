<?php
require_once 'config/database.php'; // Kết nối tới cơ sở dữ liệu
require_once 'auth/auth.php'; // Các hàm xác thực tài khoản

$post_id = $_GET['id'] ?? 0;
$error = '';
$success = '';

// Lấy danh sách chủ đề từ database để dùng cho dropdown chỉnh sửa bài viết
$topics_query = "SELECT id, name FROM topics ORDER BY name ASC";
$topics_result = mysqli_query($conn, $topics_query);
$topics = mysqli_fetch_all($topics_result, MYSQLI_ASSOC);

// Xử lý xóa bài viết
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    if (!isLoggedIn()) {
        $error = 'Vui lòng đăng nhập để xóa bài viết';
    } else {
        $user_id = $_SESSION['user_id'];
        // Lấy lại thông tin bài viết để kiểm tra quyền và lấy nội dung
        $post_query = "SELECT * FROM posts WHERE id = $post_id";
        $post_result = mysqli_query($conn, $post_query);
        $post = mysqli_fetch_assoc($post_result);
        if ($post && ($post['user_id'] == $user_id || isAdmin())) {
            // Xóa ảnh trong nội dung bài viết (nếu có)
            $content = $post['content'];
            $imgs = [];
            if (preg_match_all('/src=\"(.*?)\"/', $content, $matches)) {
                $imgs = $matches[1];
            }
            foreach ($imgs as $img_url) {
                $img_path = $_SERVER['DOCUMENT_ROOT'] . parse_url($img_url, PHP_URL_PATH);
                if (strpos($img_path, '/uploads/') !== false && file_exists($img_path)) {
                    @unlink($img_path); // Xóa file ảnh khỏi server
                }
            }
            // Xóa bài viết khỏi database
            $delete_query = "DELETE FROM posts WHERE id = $post_id";
            if (mysqli_query($conn, $delete_query)) {
                header('Location: index.php');
                exit();
            } else {
                $error = 'Xóa bài viết thất bại';
            }
        } else {
            $error = 'Bạn không có quyền xóa bài viết này';
        }
    }
}

// Lấy thông tin chi tiết bài viết (kèm tên, avatar tác giả và số lượt thích/không thích)
$post_query = "SELECT p.*, u.username, u.first_name, u.last_name, u.avatar,
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND type = 'like') as like_count,
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND type = 'dislike') as dislike_count
               FROM posts p
               JOIN users u ON p.user_id = u.id
               WHERE p.id = $post_id";
$post_result = mysqli_query($conn, $post_query);
$post = mysqli_fetch_assoc($post_result);

if (!$post) {
    // Nếu không tìm thấy bài viết thì quay về trang chủ
    header('Location: index.php');
    exit();
}

// Xử lý khi gửi bình luận mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    if (!isLoggedIn()) {
        $error = 'Vui lòng đăng nhập để bình luận';
    } else {
        $comment = mysqli_real_escape_string($conn, $_POST['comment']);
        $user_id = $_SESSION['user_id'];
        $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        
        $comment_query = "INSERT INTO comments (post_id, user_id, parent_id, content) VALUES ($post_id, $user_id, " . ($parent_id ? $parent_id : "NULL") . ", '$comment')";
       if (mysqli_query($conn, $comment_query)) {
            $comment_id = mysqli_insert_id($conn); // Lấy ID của bình luận vừa tạo

            // Xác định người nhận thông báo
            if ($parent_id) {
                // Trả lời bình luận => người nhận là chủ bình luận gốc
                $receiver_query = "SELECT user_id FROM comments WHERE id = $parent_id";
            } else {
                // Bình luận mới => người nhận là chủ bài viết
                $receiver_query = "SELECT user_id FROM posts WHERE id = $post_id";
            }

            $receiver_result = mysqli_query($conn, $receiver_query);
            if ($receiver = mysqli_fetch_assoc($receiver_result)) {
                $receiver_id = $receiver['user_id'];

                // Không gửi thông báo nếu tự bình luận bài viết của mình
                if ($receiver_id != $user_id) {
                    $notify_query = "INSERT INTO notifications (receiver_id, sender_id, post_id, comment_id, type)
                                     VALUES ($receiver_id, $user_id, $post_id, $comment_id, 'comment')";
                    mysqli_query($conn, $notify_query);
                }
            }

            header("Location: post.php?id=$post_id");
            exit();
        } else {
            $error = 'Thêm bình luận thất bại';
        }
    }
}

// Xử lý cập nhật bình luận
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_comment'])) {
    if (!isLoggedIn()) {
        $error = 'Vui lòng đăng nhập để cập nhật bình luận';
    } else {
        $comment_id = (int)$_POST['comment_id'];
        $new_content = mysqli_real_escape_string($conn, $_POST['new_content']);
        $user_id = $_SESSION['user_id'];
        
        // Kiểm tra xem người dùng này có phải chủ bình luận hoặc là admin không
        $check_query = "SELECT user_id FROM comments WHERE id = $comment_id";
        $check_result = mysqli_query($conn, $check_query);
        $comment = mysqli_fetch_assoc($check_result);
        
        if ($comment && ($comment['user_id'] == $user_id || isAdmin())) {
            $update_query = "UPDATE comments SET content = '$new_content' WHERE id = $comment_id";
            if (mysqli_query($conn, $update_query)) {
                header("Location: post.php?id=$post_id");
                exit();
            } else {
                $error = 'Cập nhật bình luận thất bại';
            }
        } else {
            $error = 'Bạn không có quyền cập nhật bình luận này';
        }
    }
}
// Xử lý tương tác like/dislike
if (isset($_POST['action']) && isLoggedIn()) {
    $action = $_POST['action'];
    $user_id = (int)$_SESSION['user_id'];
    $post_id = (int)$post_id;

    // Xóa tương tác cũ
    $delete_query = "DELETE FROM likes WHERE post_id = $post_id AND user_id = $user_id";
    mysqli_query($conn, $delete_query);

    if ($action === 'like' || $action === 'dislike') {
        // Thêm tương tác mới
        $like_query = "INSERT INTO likes (post_id, user_id, type) VALUES ($post_id, $user_id, '$action')";
        mysqli_query($conn, $like_query);
        // Gửi thông báo nếu là like
        // Chỉ gửi thông báo nếu người dùng không tự like bài viết của mình
        if ($action === 'like') {
            $receiver_result = mysqli_query($conn, "SELECT user_id FROM posts WHERE id = $post_id LIMIT 1");
            if ($receiver_result && mysqli_num_rows($receiver_result) > 0) {
                $receiver_data = mysqli_fetch_assoc($receiver_result);
                $receiver_id = (int)$receiver_data['user_id'];

                if ($receiver_id !== $user_id) {
                    // Kiểm tra thông báo like đã tồn tại chưa, nếu chưa thì thêm
                    $check_notify = "SELECT id FROM notifications WHERE receiver_id = $receiver_id AND sender_id = $user_id AND post_id = $post_id AND type = 'like' LIMIT 1";
                    $result_check = mysqli_query($conn, $check_notify);
                    if (!$result_check || mysqli_num_rows($result_check) == 0) {
                        $notify_query = "INSERT INTO notifications (receiver_id, sender_id, post_id, type) VALUES ($receiver_id, $user_id, $post_id, 'like')";
                        mysqli_query($conn, $notify_query);
                    }
                }
            }
        } else if ($action === 'dislike') {
            $receiver_result = mysqli_query($conn, "SELECT user_id FROM posts WHERE id = $post_id LIMIT 1");
            if ($receiver_result && mysqli_num_rows($receiver_result) > 0) {
                $receiver_data = mysqli_fetch_assoc($receiver_result);
                $receiver_id = (int)$receiver_data['user_id'];

                if ($receiver_id !== $user_id) {
                    // Kiểm tra thông báo dislike đã tồn tại chưa, nếu chưa thì thêm
                    $check_notify = "SELECT id FROM notifications WHERE receiver_id = $receiver_id AND sender_id = $user_id AND post_id = $post_id AND type = 'dislike' LIMIT 1";
                    $result_check = mysqli_query($conn, $check_notify);
                    if (!$result_check || mysqli_num_rows($result_check) == 0) {
                        $notify_query = "INSERT INTO notifications (receiver_id, sender_id, post_id, type) VALUES ($receiver_id, $user_id, $post_id, 'dislike')";
                        mysqli_query($conn, $notify_query);
                    }
                }
            }
        }
    } else if ($action === 'unlike') {
        // Nếu có hành động hủy like, cần xóa thông báo like nếu có
        $receiver_result = mysqli_query($conn, "SELECT user_id FROM posts WHERE id = $post_id LIMIT 1");
        if ($receiver_result && mysqli_num_rows($receiver_result) > 0) {
            $receiver_data = mysqli_fetch_assoc($receiver_result);
            $receiver_id = (int)$receiver_data['user_id'];
            if ($receiver_id !== $user_id) {
                $delete_notify = "DELETE FROM notifications WHERE receiver_id = $receiver_id AND sender_id = $user_id AND post_id = $post_id AND type = 'like'";
                mysqli_query($conn, $delete_notify);
                // Xóa thông báo dislike nếu có hành động hủy dislike
                $delete_notify_dislike = "DELETE FROM notifications WHERE receiver_id = $receiver_id AND sender_id = $user_id AND post_id = $post_id AND type = 'dislike'";
                mysqli_query($conn, $delete_notify_dislike);
            }
        }
    }

    header("Location: post.php?id=$post_id");
    exit();
}

// Xử lý xóa bình luận
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment'])) {
    if (!isLoggedIn()) {
        $error = 'Vui lòng đăng nhập để xóa bình luận'; 
    } else {
        $comment_id = (int)$_POST['delete_comment']; 
        $user_id = $_SESSION['user_id']; 
        // Kiểm tra xem người dùng này có phải chủ bình luận hoặc là admin không
        $check_query = "SELECT user_id FROM comments WHERE id = $comment_id";
        $check_result = mysqli_query($conn, $check_query);
        $comment = mysqli_fetch_assoc($check_result);
        if ($comment && ($comment['user_id'] == $user_id || isAdmin())) {
            $delete_query = "DELETE FROM comments WHERE id = $comment_id";
            if (mysqli_query($conn, $delete_query)) {
                header("Location: post.php?id=$post_id");
                exit();
            } else {
                $error = 'Xóa bình luận thất bại';
            }
        } else {
            $error = 'Bạn không có quyền xóa bình luận này';
        }
    }
}

// Xử lý cập nhật bài viết (chỉ chủ bài viết hoặc admin mới được sửa)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_post'])) {
    if (!isLoggedIn()) {
        $error = 'Vui lòng đăng nhập để cập nhật bài viết';
    } else {
        $new_title = mysqli_real_escape_string($conn, $_POST['new_title']);
        $new_content = mysqli_real_escape_string($conn, $_POST['new_content']);
        $new_topic_id = $_POST['topic_id'] ?? null; // Lấy topic_id (chủ đề) được chọn từ form chỉnh sửa bài viết

        // Kiểm tra hợp lệ cho topic_id mới
        $valid_topic_id = null; 
        if ($new_topic_id !== null && $new_topic_id !== '') {
            $new_topic_id = (int)$new_topic_id; 
            $check_topic_query = "SELECT id FROM topics WHERE id = $new_topic_id";
            $check_topic_result = mysqli_query($conn, $check_topic_query);
            if (mysqli_num_rows($check_topic_result) > 0) {
                $valid_topic_id = $new_topic_id; // Nếu tồn tại thì gán vào biến hợp lệ
            }
        }
    }
        $user_id = $_SESSION['user_id']; 
        // Kiểm tra quyền: chỉ chủ bài viết hoặc admin mới được cập nhật bài viết
        if ($post['user_id'] == $user_id || isAdmin()) {
            // Thực hiện truy vấn UPDATE, cập nhật title, content, topic_id cho bài viết
            
            $update_post_query = "UPDATE posts SET title = '$new_title', content = '$new_content', topic_id = " . ($valid_topic_id === null ? "NULL" : $valid_topic_id) . " WHERE id = $post_id";

            if (mysqli_query($conn, $update_post_query)) {

                header("Location: post.php?id=$post_id");
                exit();
            } else {
                $error = 'Cập nhật bài viết thất bại'; 
            }
        } else {
            $error = 'Bạn không có quyền cập nhật bài viết này'; 
    }
}

// Lấy danh sách bình luận gốc (không phải trả lời) của bài viết, kèm thông tin người dùng và số lượng trả lời cho mỗi bình luận
$comments_query = "SELECT c.*, u.username, u.role, u.first_name, u.last_name, u.avatar,
                  (SELECT COUNT(*) FROM comments r WHERE r.parent_id = c.id) as reply_count
                  FROM comments c
                  JOIN users u ON c.user_id = u.id
                  WHERE c.post_id = $post_id AND c.parent_id IS NULL
                  ORDER BY c.created_at DESC";
$comments_result = mysqli_query($conn, $comments_query);

// Hàm lấy danh sách trả lời cho một bình luận cụ thể, kèm thông tin người dùng (username, họ tên, avatar, role)
function getReplies($comment_id, $limit = 5, $offset = 0) {
    global $conn;
    // Truy vấn lấy các trả lời cho bình luận, kèm thông tin người dùng 
    $replies_query = "SELECT c.*, u.username, u.role, u.first_name, u.last_name, u.avatar
                     FROM comments c
                     JOIN users u ON c.user_id = u.id
                     WHERE c.parent_id = $comment_id
                     ORDER BY c.created_at ASC
                     LIMIT $limit OFFSET $offset";
    return mysqli_query($conn, $replies_query);
}

//  XỬ LÝ LƯU BÀI VIẾT YÊU THÍCH 
if (isLoggedIn() && isset($_POST['bookmark_post'])) {
    $user_id = $_SESSION['user_id'];
    // Kiểm tra đã bookmark chưa
    $checkBookmark = mysqli_query($conn, "SELECT * FROM bookmarks WHERE user_id = $user_id AND post_id = $post_id");
    if (mysqli_num_rows($checkBookmark) == 0) {
        // Nếu chưa bookmark thì thêm mới
        mysqli_query($conn, "INSERT INTO bookmarks (user_id, post_id, created_at) VALUES ($user_id, $post_id, NOW())");
    }
    // Sau khi bookmark xong, reload lại trang để cập nhật giao diện
    header("Location: post.php?id=$post_id");
    exit();
}

// GHI NHẬN LỊCH SỬ ĐỌC BÀI VIẾT 
if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    // Ghi nhận lịch sử đọc, nếu đã có thì cập nhật thời gian, nếu chưa có thì thêm mới
    $checkHistory = mysqli_query($conn, "SELECT * FROM read_history WHERE user_id = $user_id AND post_id = $post_id");
    if (mysqli_num_rows($checkHistory) > 0) { 
        mysqli_query($conn, "UPDATE read_history SET last_read_at = NOW() WHERE user_id = $user_id AND post_id = $post_id");
    } else {
        mysqli_query($conn, "INSERT INTO read_history (user_id, post_id, last_read_at) VALUES ($user_id, $post_id, NOW())");
    }
}

// Đánh dấu thông báo là đã xem khi người dùng truy cập vào bài viết từ thông báo
if (isset($_GET['notification_id']) && isLoggedIn()) {
    $notificationId = intval($_GET['notification_id']);
    $userId = $_SESSION['user_id'];

    $sql = "UPDATE notifications SET seen = 1 WHERE id = ? AND receiver_id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, 'ii', $notificationId, $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?> - Blog Chia Sẻ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?=$baseUrl?>/global.css">
</head>
<body>
<?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-body">
                <?php if (isLoggedIn() && ($post['user_id'] == $_SESSION['user_id'] || isAdmin())): ?>
                    <!-- Edit & Delete Post Buttons -->
                    <div class="mb-3 d-flex gap-2">
                        <button class="btn btn-warning" onclick="showEditPostModal()"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-danger" onclick="confirmDeletePost()"><i class="bi bi-trash"></i></button>
                    </div>
                <?php endif; ?>
                <h1 class="card-title"><?php echo htmlspecialchars($post['title']); ?></h1>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <small class="text-muted">
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
                        Bởi <img src="<?=$authorAvatarPath?>" alt="Avatar" class="rounded-circle me-1" style="width: 20px; height: 20px; object-fit: cover;">
                        <?=$authorDisplayName?>
                    </small>
                    <small class="text-muted"><?php echo date('d/m/Y', strtotime($post['created_at'])); ?></small>
                </div>
                <div class="card-text"><?php echo $post['content']; ?></div>
                
                <?php if (isLoggedIn()): ?>
                    <div class="d-flex gap-2 mt-3">
                        <form method="POST" action="" class="d-inline">
                            <input type="hidden" name="action" value="like">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="bi bi-hand-thumbs-up"></i> <?php echo $post['like_count']; ?> Thích
                            </button>
                        </form>
                        <form method="POST" action="" class="d-inline">
                            <input type="hidden" name="action" value="dislike">
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="bi bi-hand-thumbs-down"></i> <?php echo $post['dislike_count']; ?> Không thích
                            </button>
                        </form>
                        <button class="btn btn-outline-secondary" onclick="sharePost()">
                            <i class="bi bi-share"></i> Chia sẻ
                        </button>
                        <?php
                        // Kiểm tra trạng thái đã bookmark chưa
                        $user_id = $_SESSION['user_id'];
                        $isBookmarked = false;
                        $bookmarkCheck = mysqli_query($conn, "SELECT 1 FROM bookmarks WHERE user_id = $user_id AND post_id = $post_id LIMIT 1");
                        if ($bookmarkCheck && mysqli_num_rows($bookmarkCheck) > 0) {
                            $isBookmarked = true;
                        }
                        ?>
                        <form method="POST" action="" class="d-inline">
                            <input type="hidden" name="bookmark_post" value="1">
                            <button type="submit" class="btn btn-outline-success" <?php if ($isBookmarked) echo 'disabled'; ?>>
                                <i class="bi bi-bookmark<?php if ($isBookmarked) echo '-fill'; ?>"></i> <?php echo $isBookmarked ? 'Đã lưu' : 'Lưu bài viết'; ?>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Bình luận</h3>
            </div>
            <div class="card-body">
                <?php if (isLoggedIn()): ?>
                    <form method="POST" action="" class="mb-4">
                        <div class="mb-3">
                            <textarea class="form-control" name="comment" rows="3" required placeholder="Viết bình luận..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Gửi bình luận</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info">
                        Vui lòng <a href="login.php">đăng nhập</a> để bình luận
                    </div>
                <?php endif; ?>

                <div class="comments-list">
                    <?php while ($comment = mysqli_fetch_assoc($comments_result)): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="card-subtitle text-muted d-flex align-items-center">
                                        <?php
                                        // Hiển thị tác giả bình luận với ảnh đại diện và tên ưa thích
                                        $commentAuthorDisplayName = htmlspecialchars($comment['username']);
                                        if (!empty($comment['first_name']) && !empty($comment['last_name'])) {
                                            $commentAuthorDisplayName = htmlspecialchars($comment['first_name']) . ' ' . htmlspecialchars($comment['last_name']);
                                        } else if (!empty($comment['first_name'])) {
                                             $commentAuthorDisplayName = htmlspecialchars($comment['first_name']);
                                        } else if (!empty($comment['last_name'])) {
                                             $commentAuthorDisplayName = htmlspecialchars($comment['last_name']);
                                        }
                                        $commentAvatarPath = $baseUrl . '/dist/avatars/' . htmlspecialchars($comment['avatar'] ?? 'default_avatar.png');
                                        ?>
                                        <img src="<?=$commentAvatarPath?>" alt="Avatar" class="rounded-circle me-1" style="width: 20px; height: 20px; object-fit: cover;">
                                        <?=$commentAuthorDisplayName?><?php if (isset($comment['role']) && $comment['role'] === 'admin'): ?><span class="badge bg-danger ms-1">Admin</span><?php endif; ?>
                                    </h6>
                                    <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?></small>
                                </div>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                                <?php if (isLoggedIn() && ($_SESSION['user_id'] == $comment['user_id'] || isAdmin())): ?>
                                    <div class="btn-group mb-2">
                                        <button class="btn btn-sm btn-outline-primary" onclick="editComment(<?php echo $comment['id']; ?>, '<?php echo addslashes($comment['content']); ?>')"><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteComment(<?php echo $comment['id']; ?>)"><i class="bi bi-trash"></i></button>
                                    </div>
                                <?php endif; ?>

                                <!-- Replies Section -->
                                <div id="replies-<?php echo $comment['id']; ?>" class="mt-3 ms-4">
                                    <?php 
                                    $reply_limit = 5;
                                    // Gọi hàm getReplies để lấy danh sách trả lời cho bình luận hiện tại (theo id)
                                    $replies = getReplies($comment['id'], $reply_limit);
                                    // Lấy tổng số trả lời cho bình luận này (đã truy vấn sẵn ở reply_count)
                                    $reply_count = (int)$comment['reply_count'];
                                    $shown_replies = 0;
                                    // Duyệt qua từng trả lời và hiển thị ra giao diện
                                    while ($reply = mysqli_fetch_assoc($replies)):
                                        $shown_replies++;
                                    ?>
                                        <div class="card mb-2">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h6 class="card-subtitle text-muted d-flex align-items-center">
                                                        <?php
                                                        // Hiển thị tên người trả lời (ưu tiên họ tên, nếu không có thì dùng username)
                                                        $replyAuthorDisplayName = htmlspecialchars($reply['username']);
                                                        if (!empty($reply['first_name']) && !empty($reply['last_name'])) {
                                                            $replyAuthorDisplayName = htmlspecialchars($reply['first_name']) . ' ' . htmlspecialchars($reply['last_name']);
                                                        } else if (!empty($reply['first_name'])) {
                                                             $replyAuthorDisplayName = htmlspecialchars($reply['first_name']);
                                                        } else if (!empty($reply['last_name'])) {
                                                             $replyAuthorDisplayName = htmlspecialchars($reply['last_name']);
                                                        }
                                                        // Lấy đường dẫn avatar của người trả lời (nếu không có thì dùng avatar mặc định)
                                                        $replyAvatarPath = $baseUrl . '/dist/avatars/' . htmlspecialchars($reply['avatar'] ?? 'default_avatar.png');
                                                        ?>
                                                        <img src="<?=$replyAvatarPath?>" alt="Avatar" class="rounded-circle me-1" style="width: 20px; height: 20px; object-fit: cover;">
                                                        <?=$replyAuthorDisplayName?><?php if (isset($reply['role']) && $reply['role'] === 'admin'): ?><span class="badge bg-danger ms-1">Admin</span><?php endif; ?>
                                                    </h6>
                                                    <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($reply['created_at'])); ?></small>
                                                </div>
                                                <p class="card-text"><?php echo nl2br(htmlspecialchars($reply['content'])); ?></p>
                                                <?php 
                                                // Nếu là chủ trả lời hoặc admin thì hiển thị nút sửa/xóa
                                                if (isLoggedIn() && ($_SESSION['user_id'] == $reply['user_id'] || isAdmin())): ?>
                                                    <div class="btn-group">
                                                        <button class="btn btn-sm btn-outline-primary" onclick="editComment(<?php echo $reply['id']; ?>, '<?php echo addslashes($reply['content']); ?>')"><i class="bi bi-pencil"></i></button>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteComment(<?php echo $reply['id']; ?>)"><i class="bi bi-trash"></i></button>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                    <?php if ($reply_count > $reply_limit): ?>
                                        <button class="btn btn-link p-0" onclick="showAllReplies(<?php echo $comment['id']; ?>, <?php echo $reply_limit; ?>)">Xem thêm...</button>
                                    <?php endif; ?>
                                </div>

                                <!-- Reply Form (Hidden by default) -->
                                <div id="reply-form-<?php echo $comment['id']; ?>" class="mt-3 ms-4" style="display: none;">
                                    <form method="POST" action="">
                                        <input type="hidden" name="parent_id" value="<?php echo $comment['id']; ?>">
                                        <div class="mb-3">
                                            <textarea class="form-control" name="comment" rows="2" required placeholder="Viết trả lời..."></textarea>
                                        </div>
                                        <div class="btn-group">
                                            <button type="submit" class="btn btn-primary btn-sm">Gửi trả lời</button>
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="hideReplyForm(<?php echo $comment['id']; ?>)">Hủy</button>
                                        </div>
                                    </form>
                                </div>

                                <!-- Reply Button (luôn ở cuối cùng) -->
                                <?php if (isLoggedIn()): ?>
                                    <button class="btn btn-sm btn-outline-secondary mt-2" onclick="showReplyForm(<?php echo $comment['id']; ?>)">Trả lời</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Edit Comment Modal -->
    <div class="modal fade" id="editCommentModal" tabindex="-1" aria-labelledby="editCommentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCommentModalLabel">Sửa bình luận</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="comment_id" id="editCommentId">
                        <input type="hidden" name="update_comment" value="1">
                        <div class="mb-3">
                            <label for="new_content" class="form-label">Nội dung</label>
                            <textarea class="form-control" id="new_content" name="new_content" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Form ẩn để xóa comment -->
    <form id="deleteCommentForm" method="POST" action="" style="display:none;">
        <input type="hidden" name="delete_comment" id="deleteCommentInput">
    </form>

    <!-- Edit Post Modal -->
    <div class="modal fade" id="editPostModal" tabindex="-1" aria-labelledby="editPostModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPostModalLabel"><i class="bi bi-pencil-square"></i></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <form method="POST" action="" onsubmit="return submitEditPostQuill();">
                    <div class="modal-body">
                        <input type="hidden" name="update_post" value="1">
                        <div class="mb-3">
                            <label for="new_title" class="form-label">Tiêu đề</label>
                            <input type="text" class="form-control" id="new_title" name="new_title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
                        </div>
                        
                         <!-- Topic Selection Dropdown -->
                        <div class="mb-3">
                            <label for="edit_topic" class="form-label">Chủ đề</label>
                            <select class="form-select" id="edit_topic" name="topic_id">
                                <option value="">-- Chọn chủ đề --</option>
                                <?php foreach ($topics as $topic): ?>
                                    <option value="<?php echo $topic['id']; ?>"><?php echo htmlspecialchars($topic['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="quill-edit-post" class="form-label">Nội dung</label>
                            <div id="quill-edit-post" style="height: 300px;"></div>
                            <input type="hidden" id="hidden_new_content" name="new_content">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Form ẩn để xóa bài viết -->
    <form id="deletePostForm" method="POST" action="" style="display:none;">
        <input type="hidden" name="delete_post" value="1">
    </form>

    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.7/quill.js"></script>
    <script>
        // Khởi tạo trình soạn thảo QuillJS cho phần chỉnh sửa bài viết
        document.addEventListener('DOMContentLoaded', function() {
            quillEditPost = new Quill('#quill-edit-post', {
                theme: 'snow',
                modules: {
                    toolbar: {
                        container: [
                            [{ header: [1, 2, 3, false] }],
                            ['bold', 'italic', 'underline', 'strike'],
                            ['blockquote', 'code-block'],
                            [{ list: 'ordered' }, { list: 'bullet' }],
                            [{ script: 'sub' }, { script: 'super' }],
                            [{ indent: '-1' }, { indent: '+1' }],
                            [{ direction: 'rtl' }],
                            [{ color: [] }, { background: [] }],
                            [{ align: [] }],
                            ['link', 'image'],
                            ['clean']
                        ],
                        handlers: {
                            image: imageHandlerEditPost // Gán handler cho nút chèn ảnh
                        }
                    }
                }
            });
            // Xử lý sự kiện dán ảnh từ clipboard vào editor
            quillEditPost.root.addEventListener('paste', function(e) {
                var clipboardData = e.clipboardData || window.clipboardData;
                if (clipboardData && clipboardData.items) {
                    for (var i = 0; i < clipboardData.items.length; i++) {
                        var item = clipboardData.items[i];
                        if (item.type.indexOf('image') !== -1) {
                            e.preventDefault();
                            var file = item.getAsFile();
                            uploadImageToServerEditPost(file, function(url) {
                                var range = quillEditPost.getSelection();
                                quillEditPost.insertEmbed(range.index, 'image', url);
                            });
                        }
                    }
                }
            });
            // Xử lý sự kiện kéo-thả ảnh vào editor
            quillEditPost.root.addEventListener('drop', function(e) {
                if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
                    e.preventDefault();
                    var file = e.dataTransfer.files[0];
                    if (file && file.type.indexOf('image') !== -1) {
                        uploadImageToServerEditPost(file, function(url) {
                            var range = quillEditPost.getSelection();
                            quillEditPost.insertEmbed(range.index, 'image', url);
                        });
                    }
                }
            });
        });
        // Hàm xử lý khi người dùng bấm nút chèn ảnh trên thanh công cụ QuillJS
        function imageHandlerEditPost() {
            var input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');
            input.click(); 
            input.onchange = function() { 
                var file = input.files[0]; 
                if (file) { 
                    uploadImageToServerEditPost(file, function(url) {
                        var range = quillEditPost.getSelection();
                        quillEditPost.insertEmbed(range.index, 'image', url);
                    });
                }
            };
        }
        // Hàm upload ảnh lên server khi dán/kéo-thả/chọn ảnh
        function uploadImageToServerEditPost(file, callback) {
            var formData = new FormData();
            formData.append('image', file);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'upload_image.php', true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var res = JSON.parse(xhr.responseText);
                    if (res.url) {
                        callback(res.url); // Trả về url ảnh đã upload thành công
                    } else {
                        alert(res.error || 'Lỗi upload ảnh');
                    }
                } else {
                    alert('Lỗi upload ảnh');
                }
            };
            xhr.send(formData);
        }
        // Hàm xử lý khi submit form chỉnh sửa bài viết:
        function submitEditPostQuill() {
            var html = quillEditPost.root.innerHTML.replace(/<img[^>]+src=["']data:image\/(png|jpeg|jpg|gif|webp);base64,[^"']+["'][^>]*>/gi, '');
            document.getElementById('hidden_new_content').value = html;
            var topicIdSelect = document.getElementById('edit_topic');
            var selectedTopicId = topicIdSelect.value;
            var topicIdInput = document.createElement('input');
            topicIdInput.setAttribute('type', 'hidden');
            topicIdInput.setAttribute('name', 'topic_id');
            topicIdInput.setAttribute('value', selectedTopicId);
            document.querySelector('#editPostModal form').appendChild(topicIdInput);
            return true;
        }
        // Hàm mở modal chỉnh sửa bài viết, nạp lại nội dung và chủ đề hiện tại vào editor và dropdown
        function showEditPostModal() {
            var modal = new bootstrap.Modal(document.getElementById('editPostModal'));
            modal.show();
            setTimeout(function() {
                quillEditPost.root.innerHTML = <?php echo json_encode($post['content']); ?>;
                var currentTopicId = <?php echo json_encode($post['topic_id']); ?>;
                if (currentTopicId) {
                    document.getElementById('edit_topic').value = currentTopicId;
                }
            }, 300);
        }
        // Hàm chia sẻ bài viết qua Web Share API hoặc copy link nếu không hỗ trợ
        function sharePost() {
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo addslashes($post['title']); ?>',
                    text: '<?php echo addslashes(mb_substr($post['content'], 0, 100)); ?>...',
                    url: window.location.href
                });
            } else {
                // Giải pháp dự phòng cho các trình duyệt không hỗ trợ Web Share API
                const dummy = document.createElement('input');
                document.body.appendChild(dummy);
                dummy.value = window.location.href;
                dummy.select();
                document.execCommand('copy');
                document.body.removeChild(dummy);
                alert('Đã sao chép liên kết!');
            }
        }

        // Hàm mở modal sửa bình luận, nạp nội dung bình luận vào form
        function editComment(commentId, content) {
            document.getElementById('editCommentId').value = commentId;
            document.getElementById('new_content').value = content;
            new bootstrap.Modal(document.getElementById('editCommentModal')).show();
        }

        // Hàm xác nhận và gửi form xóa bình luận
        function deleteComment(commentId) {
            if (confirm('Bạn có chắc chắn muốn xóa bình luận này?')) {
                document.getElementById('deleteCommentInput').value = commentId;
                document.getElementById('deleteCommentForm').submit();
            }
        }

        // Hàm hiển thị form trả lời cho một bình luận cụ thể
        function showReplyForm(commentId) {
            document.getElementById('reply-form-' + commentId).style.display = 'block';
        }

        // Hàm ẩn form trả lời cho một bình luận cụ thể
        function hideReplyForm(commentId) {
            document.getElementById('reply-form-' + commentId).style.display = 'none';
        }

        // Hàm chuyển hướng để hiển thị tất cả các trả lời của một bình luận (phân trang replies)
        function showAllReplies(commentId, limit) {
            window.location.href = window.location.pathname + window.location.search + '&show_all_replies=' + commentId;
        }

        // Hàm xác nhận và gửi form xóa bài viết
        function confirmDeletePost() {
            if (confirm('Bạn có chắc chắn muốn xóa bài viết này?')) {
                document.getElementById('deletePostForm').submit();
            }
        }
    </script>
</body>
</html>
<?php include 'footer.php'; ?>