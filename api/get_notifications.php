<?php
require_once '../config/database.php';
require_once '../auth/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Bạn cần đăng nhập để xem thông báo.']);
    exit;
}

$user_id = $_SESSION['user_id'];

function fetchNotifications($conn, $user_id) {
    $query = "SELECT id, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    return null;
}

$notifications = fetchNotifications($conn, $user_id);

if ($notifications !== null) {
    echo json_encode($notifications);
} else {
    echo json_encode(['error' => 'Không thể lấy thông báo.']);
}

mysqli_stmt_close($stmt);
?>
