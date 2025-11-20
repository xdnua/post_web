<?php
require_once __DIR__ . '/connect.php';

$post_id = $_GET['id'] ?? 0; // Lấy ID bài viết từ URL, mặc định là 0 nếu không có
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
                header('Location: /posts/index.php');
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
        $parent_id = isset($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;

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

            header("Location: /posts/src/views/post/post.php?id=$post_id");
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
        $comment_id = (int) $_POST['comment_id'];
        $new_content = mysqli_real_escape_string($conn, $_POST['new_content']);
        $user_id = $_SESSION['user_id'];

        // Kiểm tra xem người dùng này có phải chủ bình luận hoặc là admin không
        $check_query = "SELECT user_id FROM comments WHERE id = $comment_id";
        $check_result = mysqli_query($conn, $check_query);
        $comment = mysqli_fetch_assoc($check_result);

        if ($comment && ($comment['user_id'] == $user_id || isAdmin())) {
            $update_query = "UPDATE comments SET content = '$new_content' WHERE id = $comment_id";
            if (mysqli_query($conn, $update_query)) {
                header("Location: /posts/src/views/post/post.php?id=$post_id");
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
    $user_id = (int) $_SESSION['user_id'];
    $post_id = (int) $post_id;

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
                $receiver_id = (int) $receiver_data['user_id'];

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
                $receiver_id = (int) $receiver_data['user_id'];

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
            $receiver_id = (int) $receiver_data['user_id'];
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
        $comment_id = (int) $_POST['delete_comment'];
        $user_id = $_SESSION['user_id'];
        // Kiểm tra xem người dùng này có phải chủ bình luận hoặc là admin không
        $check_query = "SELECT user_id FROM comments WHERE id = $comment_id";
        $check_result = mysqli_query($conn, $check_query);
        $comment = mysqli_fetch_assoc($check_result);
        if ($comment && ($comment['user_id'] == $user_id || isAdmin())) {
            $delete_query = "DELETE FROM comments WHERE id = $comment_id";
            if (mysqli_query($conn, $delete_query)) {
                header("Location: /posts/src/views/post/post.php?id=$post_id");
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
            $new_topic_id = (int) $new_topic_id;
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

            header("Location: /posts/src/views/post/post.php?id=$post_id");
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
function getReplies($comment_id, $limit = 5, $offset = 0)
{
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