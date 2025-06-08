<?php
require_once '../config/database.php';
require_once '../auth/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Bạn cần đăng nhập để xem lịch sử đọc bài viết.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$query = "SELECT posts.id, posts.title, read_history.last_read_at 
          FROM read_history 
          JOIN posts ON read_history.post_id = posts.id 
          WHERE read_history.user_id = $user_id 
          ORDER BY read_history.last_read_at DESC";

$result = mysqli_query($conn, $query);

if ($result) {
    $history = mysqli_fetch_all($result, MYSQLI_ASSOC);
    echo json_encode($history);
} else {
    echo json_encode(['error' => 'Không thể lấy lịch sử đọc bài viết.']);
}
?>
