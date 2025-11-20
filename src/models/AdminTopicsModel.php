<?php
require_once __DIR__ . '/../controllers/connect.php';

class AdminTopicModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Lấy tất cả chủ đề
    public function getAllTopics()
    {
        $sql = "SELECT * FROM topics ORDER BY id ASC";
        return mysqli_query($this->conn, $sql);
    }

    // Thêm chủ đề
    public function addTopic($name)
    {
        $name = trim($name);
        if ($name === '')
            return false;
        $escaped = mysqli_real_escape_string($this->conn, $name);
        $sql = "INSERT INTO topics (name) VALUES ('$escaped')";
        return mysqli_query($this->conn, $sql);
    }

    // Lấy chủ đề theo ID
    public function getTopicById($id)
    {
        $id = (int) $id;
        $sql = "SELECT * FROM topics WHERE id = $id LIMIT 1";
        $result = mysqli_query($this->conn, $sql);
        return mysqli_fetch_assoc($result);
    }

    // Cập nhật chủ đề
    public function updateTopic($id, $name)
    {
        $id = (int) $id;
        $name = trim($name);
        if ($name === '')
            return false;
        $escaped = mysqli_real_escape_string($this->conn, $name);
        $sql = "UPDATE topics SET name = '$escaped' WHERE id = $id";
        return mysqli_query($this->conn, $sql);
    }

    // Xóa chủ đề
    public function deleteTopic($id)
    {
        $id = (int) $id;
        // Đặt topic_id của bài viết liên quan về NULL
        mysqli_query($this->conn, "UPDATE posts SET topic_id = NULL WHERE topic_id = $id");
        $sql = "DELETE FROM topics WHERE id = $id";
        return mysqli_query($this->conn, $sql);
    }
}
