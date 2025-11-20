<?php
require_once __DIR__ . '/../models/AdminUserModel.php';
requireAdmin();

$model = new AdminUserModel($conn);

$error = '';
$success = '';

// Xử lý xóa người dùng
if (isset($_POST['delete_user'])) {
    if ($model->deleteUser($_POST['delete_user'], $_SESSION['user_id'])) {
        $success = 'Xóa người dùng thành công';
    } else {
        $error = 'Không thể xóa người dùng (có thể là chính bạn)';
    }
}

// Xử lý cập nhật vai trò
if (isset($_POST['update_role'])) {
    if ($model->updateRole($_POST['user_id'], $_POST['role'], $_SESSION['user_id'])) {
        $success = 'Cập nhật vai trò thành công';
    } else {
        $error = 'Không thể cập nhật vai trò (có thể là chính bạn)';
    }
}

// Phân trang và tìm kiếm
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search_term = $_GET['search'] ?? '';

// Lấy dữ liệu người dùng
$total_users = $model->countUsers($search_term);
$total_pages = ceil($total_users / $limit);
$result = $model->getUsers($limit, $offset, $search_term);
?>