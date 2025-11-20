<?php
require_once __DIR__ . '/../models/MyPostModel.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$model = new MyPostModel($conn);

// Lấy thông tin người dùng
$userSql = "SELECT username, first_name, last_name, avatar FROM users WHERE id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $userSql);
$loggedInUser = null;
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $userResult = mysqli_stmt_get_result($stmt);
    $loggedInUser = mysqli_fetch_assoc($userResult);
    mysqli_stmt_close($stmt);
}

// Chuẩn bị tên hiển thị và avatar
$userDisplayName = htmlspecialchars($loggedInUser['username'] ?? '');
if ($loggedInUser && !empty($loggedInUser['first_name']) && !empty($loggedInUser['last_name'])) {
    $userDisplayName = htmlspecialchars($loggedInUser['first_name']) . ' ' . htmlspecialchars($loggedInUser['last_name']);
} elseif ($loggedInUser && !empty($loggedInUser['first_name'])) {
    $userDisplayName = htmlspecialchars($loggedInUser['first_name']);
} elseif ($loggedInUser && !empty($loggedInUser['last_name'])) {
    $userDisplayName = htmlspecialchars($loggedInUser['last_name']);
}
$userAvatarPath = $baseUrl . '/src/assets/dist/avatars/' . htmlspecialchars($loggedInUser['avatar'] ?? 'default_avatar.png');

// Phân trang và tìm kiếm
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search_term = $_GET['search'] ?? '';

// Lấy dữ liệu bài viết
$total_posts = $model->countUserPosts($user_id, $search_term);
$total_pages = ceil($total_posts / $limit);
$result = $model->getUserPosts($user_id, $limit, $offset, $search_term);
?>