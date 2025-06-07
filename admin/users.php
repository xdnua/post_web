<?php
require_once '../config/database.php';
require_once '../auth/auth.php';

// Require admin access
requireAdmin();

$error = '';
$success = '';

// Handle user deletion
if (isset($_POST['delete_user'])) {
    $user_id = (int)$_POST['delete_user'];
    if ($user_id !== $_SESSION['user_id']) { // Prevent self-deletion
        $query = "DELETE FROM users WHERE id = $user_id";
        if (mysqli_query($conn, $query)) {
            $success = 'User deleted successfully';
        } else {
            $error = 'Failed to delete user';
        }
    } else {
        $error = 'Cannot delete your own account';
    }
}

// Handle user role update
if (isset($_POST['update_role'])) {
    $user_id = (int)$_POST['user_id'];
    $new_role = $_POST['role'];
    
    if ($user_id !== $_SESSION['user_id']) { // Prevent self-role change
        $query = "UPDATE users SET role = '$new_role' WHERE id = $user_id";
        if (mysqli_query($conn, $query)) {
            $success = 'User role updated successfully';
        } else {
            $error = 'Failed to update user role';
        }
    } else {
        $error = 'Cannot change your own role';
    }
}

// Pagination and Search setup
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search_term = $_GET['search'] ?? '';
$search_condition = '';

if (!empty($search_term)) {
    $escaped_search_term = mysqli_real_escape_string($conn, $search_term);
    $search_condition = " WHERE username LIKE '%$escaped_search_term%' OR email LIKE '%$escaped_search_term%'";
}

// Count total users (with search)
$count_query = "SELECT COUNT(*) as total FROM users" . $search_condition;
$count_result = mysqli_query($conn, $count_query);
$total_users = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_users / $limit);

// Get users for current page (with search)
$query = "SELECT id, username, email, role, created_at, first_name, last_name, avatar FROM users" . $search_condition . " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $query);

$baseUrl = '/posts'; // Đổi thành tên thư mục dự án của bạn

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý người dùng - Blog Chia Sẻ</title>
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
                <li class="active">
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
                    <h3 class="mb-0">Danh sách người dùng</h3>
                </div>
                <div class="card-body">

                    <form method="GET" action="" class="mb-4">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Tìm kiếm người dùng..." value="<?php echo htmlspecialchars($search_term); ?>">
                            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Tìm kiếm</button>
                            <?php if (!empty($search_term)): ?>
                                <a href="admin/users.php" class="btn btn-secondary">Hủy tìm kiếm</a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result) > 0): ?>
                                    <?php while ($user = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?php echo $user['id']; ?></td>
                                            <td>
                                                <?php
                                                $userDisplayName = htmlspecialchars($user['username']);
                                                if (!empty($user['first_name']) && !empty($user['last_name'])) {
                                                    $userDisplayName = htmlspecialchars($user['first_name']) . ' ' . htmlspecialchars($user['last_name']);
                                                } else if (!empty($user['first_name'])) {
                                                     $userDisplayName = htmlspecialchars($user['first_name']);
                                                } else if (!empty($user['last_name'])) {
                                                     $userDisplayName = htmlspecialchars($user['last_name']);
                                                }
                                                $userAvatarPath = $baseUrl . '/dist/avatars/' . htmlspecialchars($user['avatar'] ?? 'default_avatar.png');
                                                ?>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?=$userAvatarPath?>" alt="Avatar" class="rounded-circle me-1" style="width: 20px; height: 20px; object-fit: cover;">
                                                    <?=$userDisplayName?>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                                    <form method="POST" action="" class="d-inline">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <select name="role" class="form-select form-select-sm" onchange="this.form.submit()">
                                                            <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                        </select>
                                                        <input type="hidden" name="update_role" value="1">
                                                    </form>
                                                <?php else: ?>
                                                    <span class="badge bg-primary"><?php echo $user['role']; ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                                    <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                        <input type="hidden" name="delete_user" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">
                                                            <i class="bi bi-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            <div class="alert alert-info text-center mt-3 mb-0 mx-auto gradient-bg text-light" style="max-width: 300px;">
                                                <i class="bi bi-emoji-frown" style="font-size:2rem;"></i><br>
                                                Không có người dùng nào để hiển thị.
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