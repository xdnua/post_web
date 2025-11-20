<?php
require_once __DIR__ . '/../models/BookmarksModel.php';

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    header('Location: /posts/src/views/auth/login.php');
    exit;
}

$model = new BookmarksModel($conn);

// Xử lý xóa bookmark
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post_id'])) {
    $model->deleteBookmark((int) $_POST['delete_post_id'], $user_id);
    header('Location: my_bookmarks.php');
    exit();
}

// Lấy danh sách bookmarks
$bookmarks = $model->getUserBookmarks($user_id);

// Lấy thông tin người dùng
$userSql = "SELECT username, first_name, last_name, avatar FROM users WHERE id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $userSql);
$loggedInUser = null;
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $loggedInUser = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// Chuẩn bị hiển thị tên người dùng
if ($loggedInUser) {
    if (!empty($loggedInUser['first_name']) && !empty($loggedInUser['last_name'])) {
        $userDisplayName = htmlspecialchars($loggedInUser['first_name'] . ' ' . $loggedInUser['last_name']);
    } elseif (!empty($loggedInUser['first_name'])) {
        $userDisplayName = htmlspecialchars($loggedInUser['first_name']);
    } elseif (!empty($loggedInUser['last_name'])) {
        $userDisplayName = htmlspecialchars($loggedInUser['last_name']);
    } else {
        $userDisplayName = htmlspecialchars($loggedInUser['username']);
    }
} else {
    $userDisplayName = 'Người dùng';
}

$userAvatarPath = $baseUrl . '/src/assets/dist/avatars/' . htmlspecialchars($loggedInUser['avatar'] ?? 'default_avatar.png');
?>