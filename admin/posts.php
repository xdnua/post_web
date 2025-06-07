<?php

require_once '../config/database.php'; // Kết nối tới cơ sở dữ liệu
require_once '../auth/auth.php'; // Kết nối xác thực người dùng

// Yêu cầu quyền quản trị
requireAdmin();

// Khai báo biến để lưu thông tin lỗi và thành công
$error = '';
$success = '';

// Xử lý xóa bài đăng
if (isset($_POST['delete_post'])) {
    $post_id = (int)$_POST['delete_post'];
    // Lấy nội dung bài đăng để xóa bài đăng liên quan
    $get_content = mysqli_query($conn, "SELECT content FROM posts WHERE id = $post_id");
    $row = mysqli_fetch_assoc($get_content);
    if ($row && !empty($row['content'])) {
        // Tìm tất cả các đường dẫn ảnh trong nội dung bài viết
        if (preg_match_all('/src="(.*?)"/', $row['content'], $matches)) {
            foreach ($matches[1] as $img_url) {
                $img_path = $_SERVER['DOCUMENT_ROOT'] . parse_url($img_url, PHP_URL_PATH);
                // Nếu ảnh nằm trong thư mục uploads và tồn tại thì xóa
                if (strpos($img_path, '/uploads/') !== false && file_exists($img_path)) {
                    @unlink($img_path);
                }
            }
        }
    }
    $query = "DELETE FROM posts WHERE id = $post_id";
    if (mysqli_query($conn, $query)) {
        $success = 'Xóa bài đăng thành công';
    } else {
        $error = 'Xóa bài đăng thất bại';
    }
}

// Thiết lập phân trang và tìm kiếm
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search_term = $_GET['search'] ?? '';
$search_condition = '';

// Nếu có từ khóa tìm kiếm thì thêm điều kiện vào truy vấn
if (!empty($search_term)) {
    $escaped_search_term = mysqli_real_escape_string($conn, $search_term);
    $search_condition = " WHERE p.title LIKE '%$escaped_search_term%' OR p.content LIKE '%$escaped_search_term%'";
}

// Đếm tổng số bài đăng (có áp dụng tìm kiếm nếu có)
$count_query = "SELECT COUNT(*) as total FROM posts p" . $search_condition;
$count_result = mysqli_query($conn, $count_query);
$total_posts = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_posts / $limit);

// Lấy danh sách bài đăng cho trang hiện tại kèm thông tin người dùng (có áp dụng tìm kiếm nếu có)
$query = "SELECT p.*, u.username, u.first_name, u.last_name, 
          (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND type = 'like') as like_count,
          (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND type = 'dislike') as dislike_count,
          (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
          FROM posts p 
          JOIN users u ON p.user_id = u.id ";

if (!empty($search_condition)) {
    // Nếu đã có WHERE thì thay bằng AND để nối điều kiện
     $query .= str_replace(' WHERE ', ' AND ', $search_condition);
} else {
     // Nếu chưa có điều kiện thì thêm WHERE mặc định
     $query .= ' WHERE 1'; // Luôn bắt đầu với WHERE để dễ nối điều kiện
}

$query .= " ORDER BY p.created_at DESC LIMIT $limit OFFSET $offset";

$result = mysqli_query($conn, $query);

// Đặt biến $baseUrl để cấu hình đường dẫn cơ sở cho các liên kết trong giao diện quản trị
$baseUrl = '/posts';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý bài đăng - Blog Chia Sẻ</title>
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
        <!-- Thanh điều hướng bên trái (Sidebar) -->
        <nav id="sidebar">
            <div class="sidebar-header">
                <h3><i class="bi bi-gear"></i> Admin Panel</h3>
            </div>

            <ul class="list-unstyled components">
                <li>
                    <a href="<?=$baseUrl?>/admin/index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                </li>
                <li class="active">
                    <a href="<?=$baseUrl?>/admin/posts.php"><i class="bi bi-journal-text"></i> Quản lý bài đăng</a>
                </li>
                <li>
                    <a href="<?=$baseUrl?>/admin/users.php"><i class="bi bi-people"></i> Quản lý người dùng</a>
                </li>
                 <li>
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

        <!-- Nội dung trang (Page Content) -->
        <div id="content">
            <!--Hiển thị thông báo lỗi hoặc thành công -->
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

                    <!-- Form tìm kiếm bài viết -->
                    <form method="GET" action="" class="mb-4">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Tìm kiếm bài viết..." value="<?php echo htmlspecialchars($search_term); ?>">
                            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Tìm kiếm</button>
                            <!-- Nút hủy tìm kiếm, chỉ hiển thị nếu có từ khóa tìm kiếm --> 
                            <?php if (!empty($search_term)): ?>
                                <a href="admin/posts.php" class="btn btn-secondary">Hủy tìm kiếm</a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tiêu đề</th>
                                    <th>Tác giả</th>
                                    <th>Ngày đăng</th>
                                    <th>Thống kê</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Hiển thị danh sách bài đăng -->
                                <?php if (mysqli_num_rows($result) > 0): ?>
                                    <?php while ($post = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?php echo $post['id']; ?></td>
                                            <td><?php echo htmlspecialchars($post['title']); ?></td>
                                            <td>
                                                <?php
                                                // Ưu tiên hiển thị họ tên nếu có, nếu không thì lấy username
                                                $authorDisplayName = htmlspecialchars($post['username']);
                                                if (!empty($post['first_name']) && !empty($post['last_name'])) {
                                                    $authorDisplayName = htmlspecialchars($post['first_name']) . ' ' . htmlspecialchars($post['last_name']);
                                                } elseif (!empty($post['first_name'])) {
                                                    $authorDisplayName = htmlspecialchars($post['first_name']);
                                                } elseif (!empty($post['last_name'])) {
                                                    $authorDisplayName = htmlspecialchars($post['last_name']);
                                                }
                                                echo $authorDisplayName;
                                                ?>
                                            </td>
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
                                                    <!-- Nút xem bài viết -->
                                                    <a href="../post.php?id=<?php echo $post['id']; ?>" class="btn btn-primary btn-sm">
                                                        <i class="bi bi-eye"></i> Xem
                                                    </a>
                                                    <!-- Nút xóa bài viết, xác nhận trước khi xóa -->
                                                    <form method="POST" action="" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa bài này?');">
                                                        <input type="hidden" name="delete_post" value="<?php echo $post['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">
                                                            <i class="bi bi-trash"></i> Xóa
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- Kết thúc vòng lặp hiển thị bài đăng -->
                                    <?php endwhile; ?>
                                <!-- Nếu không có bài đăng nào -->
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            <div class="alert alert-info text-center mt-3 mb-0 mx-auto gradient-bg text-light" style="max-width: 300px;">
                                                <i class="bi bi-emoji-frown" style="font-size:2rem;"></i><br>
                                                Không có bài đăng nào để hiển thị.
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Phân trang nếu có nhiều trang -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation">
              <ul class="pagination justify-content-center mt-2">
                <li class="page-item<?php if ($page <= 1) echo ' disabled'; ?>">
                  <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo !empty($search_term) ? '&search=' . htmlspecialchars($search_term) : ''; ?>" tabindex="-1">&laquo;</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                  <li class="page-item<?php if ($i == $page) echo ' active'; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search_term) ? '&search=' . htmlspecialchars($search_term) : ''; ?>"><?php echo $i; ?></a>
                  </li>
                <?php endfor; ?>
                <li class="page-item<?php if ($page >= $total_pages) echo ' disabled'; ?>">
                  <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo !empty($search_term) ? '&search=' . htmlspecialchars($search_term) : ''; ?>">&raquo;</a>
                </li>
              </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>