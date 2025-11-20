<?php include __DIR__ . '/../../controllers/AdminController.php'; ?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Blog Chia Sẻ</title>
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

        #content {
            flex: 1;
            padding: 20px;
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

        <!-- Page Content - Nội dung chính của trang admin -->
        <div id="content">
            <p>Chào mừng đến với khu vực quản trị.</p>

            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="card text-center bg-primary text-white mb-3">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-journal-text"></i> Tổng số bài đăng</h5>
                            <p class="card-text display-4"><?php echo $total_posts; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center bg-success text-white mb-3">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-people"></i> Tổng số người dùng</h5>
                            <p class="card-text display-4"><?php echo $total_users; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center bg-info text-white mb-3">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-tags"></i> Tổng số chủ đề</h5>
                            <p class="card-text display-4"><?php echo $total_topics; ?></p>
                        </div>
                    </div>
                </div>
            </div>



        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>