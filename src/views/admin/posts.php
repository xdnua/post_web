<?php include __DIR__ . '/../../controllers/AdminPostController.php'; ?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý bài đăng - Blog Chia Sẻ</title>
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
                    <h3 class="mb-0">Danh sách bài đăng</h3>
                </div>
                <div class="card-body">

                    <form method="GET" action="" class="mb-4">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Tìm kiếm bài viết..."
                                value="<?php echo htmlspecialchars($search_term); ?>">
                            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Tìm kiếm</button>
                            <?php if (!empty($search_term)): ?>
                                <a href="/posts/src/views/admin/posts.php" class="btn btn-secondary">Hủy tìm kiếm</a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tiêu đề</th>
                                    <th>Chủ đề</th>
                                    <th>Tác giả</th>
                                    <th>Ngày đăng</th>
                                    <th>Thống kê</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result) > 0): ?>
                                    <?php while ($post = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?php echo $post['id']; ?></td>
                                            <td><?php echo htmlspecialchars($post['title']); ?></td>
                                            <td>
                                                <?php if (!empty($post['topic_name'])): ?>
                                                    <span
                                                        class="badge bg-primary"><?php echo htmlspecialchars($post['topic_name']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">Không có chủ đề</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($post['username']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($post['created_at'])); ?></td>
                                            <td>
                                                <span class="badge bg-primary me-1">
                                                    <i class="bi bi-hand-thumbs-up"></i> <?php echo $post['like_count']; ?>
                                                </span>
                                                <span class="badge bg-danger me-1">
                                                    <i class="bi bi-hand-thumbs-down"></i> <?php echo $post['dislike_count']; ?>
                                                </span>
                                                <span class="badge bg-secondary">
                                                    <i class="bi bi-chat"></i> <?php echo $post['comment_count']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="<?= $baseUrl ?>/src/views/post/post.php?id=<?php echo $post['id']; ?>"
                                                        class="btn btn-primary btn-sm">
                                                        <i class="bi bi-eye"></i> Xem
                                                    </a>
                                                    <form method="POST" action="" class="d-inline"
                                                        onsubmit="return confirm('Bạn có chắc chắn muốn xóa bài này?');">
                                                        <input type="hidden" name="delete_post"
                                                            value="<?php echo $post['id']; ?>">
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
                                        <td colspan="7" class="text-center">
                                            <div class="alert alert-info text-center mt-3 mb-0 mx-auto gradient-bg text-light"
                                                style="max-width: 300px;">
                                                <i class="bi bi-emoji-frown" style="font-size:2rem;"></i><br>
                                                <?php
                                                if (!empty($search_term)) {
                                                    echo "Không tìm thấy bài đăng nào cho \"" . htmlspecialchars($search_term) . "\"";
                                                } else {
                                                    echo "Chưa có bài đăng nào!";
                                                }
                                                ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center mt-2">
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>