<?php
require_once __DIR__ . '/../controllers/connect.php';

class ReadHistoryModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getUserHistory($user_id)
    {
        $user_id = (int) $user_id;
        $sql = "SELECT posts.id, posts.title, read_history.last_read_at 
                FROM read_history 
                JOIN posts ON read_history.post_id = posts.id 
                WHERE read_history.user_id = $user_id 
                ORDER BY read_history.last_read_at DESC";
        $result = mysqli_query($this->conn, $sql);
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
}
?>