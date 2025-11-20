<?php
require_once __DIR__ . '/../controllers/connect.php';

class AdminModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getTotalPosts()
    {
        $query = "SELECT COUNT(*) as total FROM posts";
        $result = mysqli_query($this->conn, $query);
        $row = mysqli_fetch_assoc($result);
        return (int) ($row['total'] ?? 0);
    }

    public function getTotalUsers()
    {
        $query = "SELECT COUNT(*) as total FROM users";
        $result = mysqli_query($this->conn, $query);
        $row = mysqli_fetch_assoc($result);
        return (int) ($row['total'] ?? 0);
    }

    public function getTotalTopics()
    {
        $query = "SELECT COUNT(*) as total FROM topics";
        $result = mysqli_query($this->conn, $query);
        $row = mysqli_fetch_assoc($result);
        return (int) ($row['total'] ?? 0);
    }
}
?>