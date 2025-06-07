<?php
require_once '../config/database.php';
require_once '../auth/auth.php';

// Require admin access
requireAdmin();

$error = '';
$success = '';
$edit_topic = null;

// Handle Add Topic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_topic'])) {
    $topic_name = trim($_POST['topic_name']);
    if (!empty($topic_name)) {
        $escaped_name = mysqli_real_escape_string($conn, $topic_name);
        $query = "INSERT INTO topics (name) VALUES ('$escaped_name')";
        if (mysqli_query($conn, $query)) {
            $success = 'Thêm chủ đề thành công!';
        } else {
            $error = 'Lỗi: Không thể thêm chủ đề. Tên chủ đề có thể đã tồn tại.';
        }
    } else {
        $error = 'Tên chủ đề không được để trống.';
    }
    header('Location: topics.php');
    exit();
}

// Handle Delete Topic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_topic_id'])) {
    $topic_id = (int)$_POST['delete_topic_id'];
    // Optionally, handle posts associated with this topic (e.g., set topic_id to NULL)
    $update_posts_query = "UPDATE posts SET topic_id = NULL WHERE topic_id = $topic_id";
    mysqli_query($conn, $update_posts_query);
    
    $query = "DELETE FROM topics WHERE id = $topic_id";
    if (mysqli_query($conn, $query)) {
        $success = 'Xóa chủ đề thành công!';
    } else {
        $error = 'Lỗi: Không thể xóa chủ đề.';
    }
     header('Location: topics.php');
    exit();
}

// Handle Edit Topic (Fetch data)
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $query = "SELECT * FROM topics WHERE id = $edit_id LIMIT 1";
    $result = mysqli_query($conn, $query);
    $edit_topic = mysqli_fetch_assoc($result);
    if (!$edit_topic) {
        $error = 'Chủ đề không tồn tại.';
    }
}

// Handle Update Topic (Save changes)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_topic'])) {
    $topic_id = (int)$_POST['topic_id'];
    $new_name = trim($_POST['new_topic_name']);
    if (!empty($new_name)) {
        $escaped_name = mysqli_real_escape_string($conn, $new_name);
        $query = "UPDATE topics SET name = '$escaped_name' WHERE id = $topic_id";
        if (mysqli_query($conn, $query)) {
            $success = 'Cập nhật chủ đề thành công!';
        } else {
            $error = 'Lỗi: Không thể cập nhật chủ đề. Tên chủ đề có thể đã tồn tại.';
        }
    } else {
        $error = 'Tên chủ đề không được để trống.';
    }
     header('Location: topics.php');
    exit();
}


// Fetch all topics
$topics_query = "SELECT * FROM topics ORDER BY name ASC";
$topics_result = mysqli_query($conn, $topics_query);

$baseUrl = '/posts'; // Đổi thành tên thư mục dự án của bạn

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý chủ đề - Blog Chia Sẻ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?=$baseUrl?>/global.css">
    <style>
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
            padding-top: 0px !important;
        }
        .wrapper {
            display: flex;
            flex: 1;
        }
        #sidebar {
            min-width: 250px;
            max-width: 250px;
            background: #343a40;
            color: #fff;
            transition: all 0.3s;
        }
        #sidebar .sidebar-header {
            padding: 20px;
            background: #343a40;
        }
        #sidebar ul.components {
            padding: 20px 0;
            border-bottom: 1px solid #47748b;
        }
        #sidebar ul li a {
            padding: 10px;
            font-size: 1.1em;
            display: block;
            color: #fff;
            text-decoration: none;
        }
        #sidebar ul li a:hover {
            color: #343a40;
            background: #fff;
        }
         #sidebar ul li.active > a, a[aria-expanded="true"] {
            color: #fff;
            background: #6d7fcc;
        }
        #content {
            flex: 1;
            padding: 20px;
        }
    </style>
</head>
<body class="admin-page">
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="sidebar-header">
                <h3><i class="bi bi-gear"></i> Admin Panel</h3>
            </div>

            <ul class="list-unstyled components">
                <li>
                    <a href="<?=$baseUrl?>/admin/index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                </li>
                <li>
                    <a href="<?=$baseUrl?>/admin/posts.php"><i class="bi bi-journal-text"></i> Quản lý bài đăng</a>
                </li>
                <li>
                    <a href="<?=$baseUrl?>/admin/users.php"><i class="bi bi-people"></i> Quản lý người dùng</a>
                </li>
                 <li class="active">
                    <a href="<?=$baseUrl?>/admin/topics.php"><i class="bi bi-tags"></i> Quản lý chủ đề</a>
                </li>
            </ul>

             <ul class="list-unstyled components">
                <li>
                    <a href="<?=$baseUrl?>/index.php"><i class="bi bi-arrow-left"></i> Về trang chủ</a>
                </li>
                <li>
                    <a href="<?=$baseUrl?>/logout.php"><i class="bi bi-box-arrow-right"></i> Đăng xuất</a>
                </li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Danh sách chủ đề</h3>
                     <!-- Button trigger modal -->
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTopicModal">
                      Thêm chủ đề mới
                    </button>
                </div>
                <div class="card-body">

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên chủ đề</th>
                                    <th>Ngày tạo</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($topics_result) > 0): ?>
                                    <?php while ($topic = mysqli_fetch_assoc($topics_result)): ?>
                                        <tr>
                                            <td><?php echo $topic['id']; ?></td>
                                            <td><?php echo htmlspecialchars($topic['name']); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($topic['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button class="btn btn-warning btn-sm edit-topic-btn" data-id="<?php echo $topic['id']; ?>" data-name="<?php echo htmlspecialchars($topic['name']); ?>" data-bs-toggle="modal" data-bs-target="#editTopicModal">
                                                        <i class="bi bi-pencil-square"></i> Sửa
                                                    </button>
                                                    <form method="POST" action="" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa chủ đề này? Các bài viết liên quan sẽ không còn chủ đề được chọn.');">
                                                        <input type="hidden" name="delete_topic_id" value="<?php echo $topic['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">
                                                            <i class="bi bi-trash"></i> Xóa
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">
                                            <div class="alert alert-info text-center mt-3 mb-0 mx-auto gradient-bg text-light" style="max-width: 300px;">
                                                <i class="bi bi-tags" style="font-size:2rem;"></i><br>
                                                Chưa có chủ đề nào được thêm.
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Add Topic Modal -->
            <div class="modal fade" id="addTopicModal" tabindex="-1" aria-labelledby="addTopicModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addTopicModalLabel">Thêm chủ đề mới</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST" action="">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="topic-name" class="form-label">Tên chủ đề</label>
                                    <input type="text" class="form-control" id="topic-name" name="topic_name" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                <button type="submit" name="add_topic" class="btn btn-primary">Lưu chủ đề</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Topic Modal -->
            <div class="modal fade" id="editTopicModal" tabindex="-1" aria-labelledby="editTopicModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editTopicModalLabel">Sửa chủ đề</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST" action="">
                            <div class="modal-body">
                                <input type="hidden" name="topic_id" id="edit-topic-id">
                                <div class="mb-3">
                                    <label for="edit-topic-name" class="form-label">Tên chủ đề</label>
                                    <input type="text" class="form-control" id="edit-topic-name" name="new_topic_name" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                <button type="submit" name="update_topic" class="btn btn-primary">Cập nhật</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        var editTopicModal = document.getElementById('editTopicModal');
        editTopicModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var topicId = button.getAttribute('data-id');
            var topicName = button.getAttribute('data-name');
            
            var modalTopicId = editTopicModal.querySelector('#edit-topic-id');
            var modalTopicNameInput = editTopicModal.querySelector('#edit-topic-name');
            
            modalTopicId.value = topicId;
            modalTopicNameInput.value = topicName;
        });
    </script>
</body>
</html> 