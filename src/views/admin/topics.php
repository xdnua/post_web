<?php include __DIR__ . '/../../controllers/AdminTopicsController.php'; ?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý chủ đề - Blog Chia Sẻ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/src/styles/global.css">
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

        #sidebar ul li.active>a,
        a[aria-expanded="true"] {
            color: #fff;
            background: #6d7fcc;
        }

        #content {
            flex: 1;
            padding: 20px;
        }

        .card-hover {
            transition: all 0.3s ease;
            box-shadow: 0 1px 6px rgba(0, 0, 0, 0.1);
        }

        .card-hover:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>

<body class="admin-page">
    <div class="wrapper">
        <!-- Sidebar - Thanh điều hướng bên trái -->
        <nav id="sidebar">
            <div class="sidebar-header">
                <h3><i class="bi bi-gear"></i> Admin Panel</h3>
            </div>

            <ul class="list-unstyled components">
                <li>
                    <a href="<?= $baseUrl ?>/src/views/admin/index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                </li>
                <li>
                    <a href="<?= $baseUrl ?>/src/views/admin/posts.php"><i class="bi bi-journal-text"></i> Quản lý bài
                        đăng</a>
                </li>
                <li>
                    <a href="<?= $baseUrl ?>/src/views/admin/users.php"><i class="bi bi-people"></i> Quản lý người
                        dùng</a>
                </li>
                <li>
                    <a href="<?= $baseUrl ?>/src/views/admin/topics.php"><i class="bi bi-tags"></i> Quản lý chủ đề</a>
                </li>
            </ul>

            <ul class="list-unstyled components">
                <li>
                    <a href="<?= $baseUrl ?>/index.php"><i class="bi bi-arrow-left"></i> Về trang chủ</a>
                </li>
                <li>
                    <a href="<?= $baseUrl ?>/src/views/auth/logout.php"><i class="bi bi-box-arrow-right"></i> Đăng
                        xuất</a>
                </li>
            </ul>
        </nav>

        <!-- Page Content - Nội dung trang -->
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
                    <!-- Nút mở modal thêm chủ đề mới -->
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#addTopicModal">
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
                                    <!-- Lặp qua từng chủ đề để hiển thị ra bảng -->
                                    <?php while ($topic = mysqli_fetch_assoc($topics_result)): ?>
                                        <tr>
                                            <td><?php echo $topic['id']; ?></td>
                                            <td><?php echo htmlspecialchars($topic['name']); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($topic['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <!-- Nút mở modal sửa chủ đề -->
                                                    <button class="btn btn-warning btn-sm edit-topic-btn"
                                                        data-id="<?php echo $topic['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($topic['name']); ?>"
                                                        data-bs-toggle="modal" data-bs-target="#editTopicModal">
                                                        <i class="bi bi-pencil-square"></i> Sửa
                                                    </button>
                                                    <!-- Nút xóa chủ đề, xác nhận trước khi xóa -->
                                                    <form method="POST" action="" class="d-inline"
                                                        onsubmit="return confirm('Bạn có chắc chắn muốn xóa chủ đề này? Các bài viết liên quan sẽ không còn chủ đề được chọn.');">
                                                        <input type="hidden" name="delete_topic_id"
                                                            value="<?php echo $topic['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">
                                                            <i class="bi bi-trash"></i> Xóa
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <!-- Nếu không có chủ đề nào thì hiển thị thông báo -->
                                    <tr>
                                        <td colspan="4" class="text-center">
                                            <div class="alert alert-info text-center mt-3 mb-0 mx-auto gradient-bg text-light"
                                                style="max-width: 300px;">
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

            <!-- Thêm chủ đề mới: Hiển thị form để nhập tên chủ đề và lưu vào cơ sở dữ liệu -->
            <div class="modal fade" id="addTopicModal" tabindex="-1" aria-labelledby="addTopicModalLabel"
                aria-hidden="true">
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

            <!-- Chỉnh sửa và cập nhật chủ đề-->
            <div class="modal fade" id="editTopicModal" tabindex="-1" aria-labelledby="editTopicModalLabel"
                aria-hidden="true">
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
                                    <input type="text" class="form-control" id="edit-topic-name" name="new_topic_name"
                                        required>
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
        var editTopicModal = document.getElementById('editTopicModal');  // Lấy modal chỉnh sửa chủ đề
        editTopicModal.addEventListener('show.bs.modal', function (event) { // Khi modal được mở
            var button = event.relatedTarget; // Nút kích hoạt modal
            var topicId = button.getAttribute('data-id'); // Lấy ID chủ đề từ thuộc tính data-id
            var topicName = button.getAttribute('data-name'); // Lấy tên chủ đề từ thuộc tính data-name

            var modalTopicId = editTopicModal.querySelector('#edit-topic-id'); // Lấy input ẩn chứa ID chủ đề
            var modalTopicNameInput = editTopicModal.querySelector('#edit-topic-name'); // Lấy input chứa tên chủ đề

            modalTopicId.value = topicId; // Cập nhật giá trị ID chủ đề vào input ẩn
            modalTopicNameInput.value = topicName; // Cập nhật giá trị tên chủ đề vào input
        });
    </script>
</body>

</html>