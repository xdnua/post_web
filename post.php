<?php
require_once 'config/database.php';
require_once 'auth/auth.php';

$post_id = $_GET['id'] ?? 0;
$error = '';
$success = '';

// Fetch topics from the database
$topics_query = "SELECT id, name FROM topics ORDER BY name ASC";
$topics_result = mysqli_query($conn, $topics_query);
$topics = mysqli_fetch_all($topics_result, MYSQLI_ASSOC);

// Handle post deletion
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
            // Xóa ảnh trong nội dung bài viết
            $content = $post['content'];
            $imgs = [];
            if (preg_match_all('/src=\"(.*?)\"/', $content, $matches)) {
                $imgs = $matches[1];
            }
            foreach ($imgs as $img_url) {
                $img_path = $_SERVER['DOCUMENT_ROOT'] . parse_url($img_url, PHP_URL_PATH);
                if (strpos($img_path, '/uploads/') !== false && file_exists($img_path)) {
                    @unlink($img_path);
                }
            }
            // Xóa bài viết
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

// Get post details
$post_query = "SELECT p.*, u.username, u.first_name, u.last_name, u.avatar,
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND type = 'like') as like_count,
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND type = 'dislike') as dislike_count
               FROM posts p
               JOIN users u ON p.user_id = u.id
               WHERE p.id = $post_id";
$post_result = mysqli_query($conn, $post_query);
$post = mysqli_fetch_assoc($post_result);

if (!$post) {
    header('Location: index.php');
    exit();
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    if (!isLoggedIn()) {
        $error = 'Please login to comment';
    } else {
        $comment = mysqli_real_escape_string($conn, $_POST['comment']);
        $user_id = $_SESSION['user_id'];
        $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        
        $comment_query = "INSERT INTO comments (post_id, user_id, parent_id, content) VALUES ($post_id, $user_id, " . ($parent_id ? $parent_id : "NULL") . ", '$comment')";
        if (mysqli_query($conn, $comment_query)) {
            header("Location: post.php?id=$post_id");
            exit();
        } else {
            $error = 'Failed to add comment';
        }
    }
}

// Handle comment update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_comment'])) {
    if (!isLoggedIn()) {
        $error = 'Please login to update comment';
    } else {
        $comment_id = (int)$_POST['comment_id'];
        $new_content = mysqli_real_escape_string($conn, $_POST['new_content']);
        $user_id = $_SESSION['user_id'];
        
        // Check if user owns the comment or is admin
        $check_query = "SELECT user_id FROM comments WHERE id = $comment_id";
        $check_result = mysqli_query($conn, $check_query);
        $comment = mysqli_fetch_assoc($check_result);
        
        if ($comment && ($comment['user_id'] == $user_id || isAdmin())) {
            $update_query = "UPDATE comments SET content = '$new_content' WHERE id = $comment_id";
            if (mysqli_query($conn, $update_query)) {
                header("Location: post.php?id=$post_id");
                exit();
            } else {
                $error = 'Failed to update comment';
            }
        } else {
            $error = 'You do not have permission to update this comment';
        }
    }
}

// Handle like/dislike
if (isset($_POST['action']) && isLoggedIn()) {
    $action = $_POST['action'];
    $user_id = $_SESSION['user_id'];
    
    // Remove existing like/dislike
    mysqli_query($conn, "DELETE FROM likes WHERE post_id = $post_id AND user_id = $user_id");
    
    if ($action === 'like' || $action === 'dislike') {
        $like_query = "INSERT INTO likes (post_id, user_id, type) VALUES ($post_id, $user_id, '$action')";
        mysqli_query($conn, $like_query);
    }
    
    header("Location: post.php?id=$post_id");
    exit();
}

// Handle comment deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment'])) {
    if (!isLoggedIn()) {
        $error = 'Please login to delete comment';
    } else {
        $comment_id = (int)$_POST['delete_comment'];
        $user_id = $_SESSION['user_id'];
        // Check if user owns the comment or is admin
        $check_query = "SELECT user_id FROM comments WHERE id = $comment_id";
        $check_result = mysqli_query($conn, $check_query);
        $comment = mysqli_fetch_assoc($check_result);
        if ($comment && ($comment['user_id'] == $user_id || isAdmin())) {
            $delete_query = "DELETE FROM comments WHERE id = $comment_id";
            if (mysqli_query($conn, $delete_query)) {
                header("Location: post.php?id=$post_id");
                exit();
            } else {
                $error = 'Failed to delete comment';
            }
        } else {
            $error = 'You do not have permission to delete this comment';
        }
    }
}

// Handle post update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_post'])) {
    if (!isLoggedIn()) {
        $error = 'Vui lòng đăng nhập để cập nhật bài viết';
    } else {
        $new_title = mysqli_real_escape_string($conn, $_POST['new_title']);
        $new_content = mysqli_real_escape_string($conn, $_POST['new_content']);
        $new_topic_id = $_POST['topic_id'] ?? null; // Get selected topic_id from edit form

         // Validate new_topic_id
        $valid_topic_id = null;
        if ($new_topic_id !== null && $new_topic_id !== '') {
            $new_topic_id = (int)$new_topic_id;
            // Optional: Validate if the topic_id exists in the database
            $check_topic_query = "SELECT id FROM topics WHERE id = $new_topic_id";
            $check_topic_result = mysqli_query($conn, $check_topic_query);
            if (mysqli_num_rows($check_topic_result) > 0) {
                $valid_topic_id = $new_topic_id; // Use valid topic_id
            }
        }

        $user_id = $_SESSION['user_id'];
        if ($post['user_id'] == $user_id || isAdmin()) {
            // Include topic_id in the UPDATE query
            $update_post_query = "UPDATE posts SET title = '$new_title', content = '$new_content', topic_id = " . ($valid_topic_id === null ? "NULL" : $valid_topic_id) . " WHERE id = $post_id";

            if (mysqli_query($conn, $update_post_query)) {
                // Redirect to the updated post page using POST/Redirect/GET
                header("Location: post.php?id=$post_id");
                exit();
            } else {
                $error = 'Cập nhật bài viết thất bại';
            }
        } else {
            $error = 'Bạn không có quyền cập nhật bài viết này';
        }
    }
}

// Get comments with their replies
$comments_query = "SELECT c.*, u.username, u.role, u.first_name, u.last_name, u.avatar,
                  (SELECT COUNT(*) FROM comments r WHERE r.parent_id = c.id) as reply_count
                  FROM comments c
                  JOIN users u ON c.user_id = u.id
                  WHERE c.post_id = $post_id AND c.parent_id IS NULL
                  ORDER BY c.created_at DESC";
$comments_result = mysqli_query($conn, $comments_query);

// Function to get replies for a comment (with limit and offset)
function getReplies($comment_id, $limit = 5, $offset = 0) {
    global $conn;
    // Add first_name, last_name, avatar to the replies query
    $replies_query = "SELECT c.*, u.username, u.role, u.first_name, u.last_name, u.avatar
                     FROM comments c
                     JOIN users u ON c.user_id = u.id
                     WHERE c.parent_id = $comment_id
                     ORDER BY c.created_at ASC
                     LIMIT $limit OFFSET $offset";
    return mysqli_query($conn, $replies_query);
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
                                        // Display comment author with avatar and preferred name
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
                                    $replies = getReplies($comment['id'], $reply_limit);
                                    $reply_count = (int)$comment['reply_count'];
                                    $shown_replies = 0;
                                    while ($reply = mysqli_fetch_assoc($replies)):
                                        $shown_replies++;
                                    ?>
                                        <div class="card mb-2">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h6 class="card-subtitle text-muted d-flex align-items-center">
                                                        <?php
                                                        // Display reply author with avatar and preferred name
                                                        $replyAuthorDisplayName = htmlspecialchars($reply['username']);
                                                        if (!empty($reply['first_name']) && !empty($reply['last_name'])) {
                                                            $replyAuthorDisplayName = htmlspecialchars($reply['first_name']) . ' ' . htmlspecialchars($reply['last_name']);
                                                        } else if (!empty($reply['first_name'])) {
                                                             $replyAuthorDisplayName = htmlspecialchars($reply['first_name']);
                                                        } else if (!empty($reply['last_name'])) {
                                                             $replyAuthorDisplayName = htmlspecialchars($reply['last_name']);
                                                        }
                                                        $replyAvatarPath = $baseUrl . '/dist/avatars/' . htmlspecialchars($reply['avatar'] ?? 'default_avatar.png');
                                                        ?>
                                                        <img src="<?=$replyAvatarPath?>" alt="Avatar" class="rounded-circle me-1" style="width: 20px; height: 20px; object-fit: cover;">
                                                        <?=$replyAuthorDisplayName?><?php if (isset($reply['role']) && $reply['role'] === 'admin'): ?><span class="badge bg-danger ms-1">Admin</span><?php endif; ?>
                                                    </h6>
                                                    <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($reply['created_at'])); ?></small>
                                                </div>
                                                <p class="card-text"><?php echo nl2br(htmlspecialchars($reply['content'])); ?></p>
                                                <?php if (isLoggedIn() && ($_SESSION['user_id'] == $reply['user_id'] || isAdmin())): ?>
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
        var quillEditPost;
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
                            image: imageHandlerEditPost
                        }
                    }
                }
            });
            // Paste image
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
            // Drag-drop image
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
        function uploadImageToServerEditPost(file, callback) {
            var formData = new FormData();
            formData.append('image', file);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'upload_image.php', true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var res = JSON.parse(xhr.responseText);
                    if (res.url) {
                        callback(res.url);
                    } else {
                        alert(res.error || 'Lỗi upload ảnh');
                    }
                } else {
                    alert('Lỗi upload ảnh');
                }
            };
            xhr.send(formData);
        }
        function submitEditPostQuill() {
            // Loại bỏ ảnh base64 nếu còn sót lại
            var html = quillEditPost.root.innerHTML.replace(/<img[^>]+src=["']data:image\/(png|jpeg|jpg|gif|webp);base64,[^"']+["'][^>]*>/gi, '');
            document.getElementById('hidden_new_content').value = html;
             // Ensure the topic_id is also submitted
            var topicIdSelect = document.getElementById('edit_topic');
            var selectedTopicId = topicIdSelect.value;
            var topicIdInput = document.createElement('input');
            topicIdInput.setAttribute('type', 'hidden');
            topicIdInput.setAttribute('name', 'topic_id');
            topicIdInput.setAttribute('value', selectedTopicId);
            document.querySelector('#editPostModal form').appendChild(topicIdInput);

            return true;
        }
        // Khi mở modal, nạp nội dung bài viết vào Quill
        function showEditPostModal() {
            var modal = new bootstrap.Modal(document.getElementById('editPostModal'));
            modal.show();
            setTimeout(function() {
                quillEditPost.root.innerHTML = <?php echo json_encode($post['content']); ?>;
                 // Set the current topic in the dropdown
                var currentTopicId = <?php echo json_encode($post['topic_id']); ?>;
                if (currentTopicId) {
                    document.getElementById('edit_topic').value = currentTopicId;
                }
            }, 300);
        }
        function sharePost() {
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo addslashes($post['title']); ?>',
                    text: '<?php echo addslashes(mb_substr($post['content'], 0, 100)); ?>...',
                    url: window.location.href
                });
            } else {
                // Fallback for browsers that don't support Web Share API
                const dummy = document.createElement('input');
                document.body.appendChild(dummy);
                dummy.value = window.location.href;
                dummy.select();
                document.execCommand('copy');
                document.body.removeChild(dummy);
                alert('Đã sao chép liên kết!');
            }
        }

        function editComment(commentId, content) {
            document.getElementById('editCommentId').value = commentId;
            document.getElementById('new_content').value = content;
            new bootstrap.Modal(document.getElementById('editCommentModal')).show();
        }

        function deleteComment(commentId) {
            if (confirm('Bạn có chắc chắn muốn xóa bình luận này?')) {
                document.getElementById('deleteCommentInput').value = commentId;
                document.getElementById('deleteCommentForm').submit();
            }
        }

        function showReplyForm(commentId) {
            document.getElementById('reply-form-' + commentId).style.display = 'block';
        }

        function hideReplyForm(commentId) {
            document.getElementById('reply-form-' + commentId).style.display = 'none';
        }

        function showAllReplies(commentId, limit) {
            window.location.href = window.location.pathname + window.location.search + '&show_all_replies=' + commentId;
        }

        function confirmDeletePost() {
            if (confirm('Bạn có chắc chắn muốn xóa bài viết này?')) {
                document.getElementById('deletePostForm').submit();
            }
        }
    </script>
</body>
</html>
<?php include 'footer.php'; ?> 