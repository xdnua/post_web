<?php
require_once __DIR__ . '/connect.php';

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    header('Location: /login.php');
    exit;
}

// Lấy thông tin người dùng
$userSql = "SELECT username, first_name, last_name, avatar FROM users WHERE id = ? LIMIT 1";
$userStmt = mysqli_prepare($conn, $userSql);
$loggedInUser = null;
if ($userStmt) {
    mysqli_stmt_bind_param($userStmt, 'i', $user_id);
    mysqli_stmt_execute($userStmt);
    $result = mysqli_stmt_get_result($userStmt);
    $loggedInUser = mysqli_fetch_assoc($result);
    mysqli_stmt_close($userStmt);
}
// hiển thị tên người dùng
$userDisplayName = '';
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

$userAvatarPath = $baseUrl . '/dist/avatars/' . htmlspecialchars($loggedInUser['avatar'] ?? 'default_avatar.png');

// Xử lý xóa bài viết đã lưu (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post_id'])) {
    $delete_id = (int) $_POST['delete_post_id'];
    $stmt = mysqli_prepare($conn, "DELETE FROM bookmarks WHERE post_id = ? AND user_id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ii', $delete_id, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    header('Location: /posts/src/views/bookmarks/my_bookmarks.php');
    exit();
}

// Lấy danh sách bài viết đã lưu 
$list_sql = "SELECT p.*, sp.created_at,
    (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND type = 'like') AS like_count,
    (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND type = 'dislike') AS dislike_count
    FROM bookmarks sp
    JOIN posts p ON sp.post_id = p.id
    WHERE sp.user_id = ?
    ORDER BY sp.created_at DESC";

$list_stmt = mysqli_prepare($conn, $list_sql);
if ($list_stmt) {
    mysqli_stmt_bind_param($list_stmt, 'i', $user_id);
    mysqli_stmt_execute($list_stmt);
    $result = mysqli_stmt_get_result($list_stmt);
} else {
    $result = false;
}
?>