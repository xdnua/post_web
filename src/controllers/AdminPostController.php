<?php
require_once __DIR__ . '/../models/AdminPostModel.php';
requireAdmin();

$model = new AdminPostModel($conn);

$error = '';
$success = '';

// Xử lý xóa bài
if (isset($_POST['delete_post'])) {
    $post_id = (int) $_POST['delete_post'];
    if ($model->deletePost($post_id)) {
        $success = 'Xóa bài đăng thành công';
    } else {
        $error = 'Xóa bài đăng thất bại';
    }
}

// Pagination và tìm kiếm
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search_term = $_GET['search'] ?? '';

// Lấy dữ liệu
$total_posts = $model->countPosts($search_term);
$total_pages = ceil($total_posts / $limit);
$result = $model->getPosts($limit, $offset, $search_term);
?>