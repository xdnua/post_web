<?php
require_once __DIR__ . '/../controllers/connect.php';

class NotificationModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getUserNotifications($user_id)
    {
        $sql = "SELECT id, message, is_read, created_at, link, post_id
                FROM notifications
                WHERE receiver_id = ?
                ORDER BY created_at DESC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    public function markAllAsRead($user_id)
    {
        $sql = "UPDATE notifications SET is_read = 1 WHERE receiver_id = ? AND is_read = 0";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
    }
}
