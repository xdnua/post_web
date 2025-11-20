<?php
require_once __DIR__ . '/../controllers/connect.php';

class CreatePostModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Lấy danh sách chủ đề
    public function getTopics()
    {
        $sql = "SELECT id, name FROM topics ORDER BY name ASC";
        $result = mysqli_query($this->conn, $sql);
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    // Thêm bài viết mới
    public function addPost($user_id, $title, $content, $topic_id = null)
    {
        $title = mysqli_real_escape_string($this->conn, $title);
        $content = mysqli_real_escape_string($this->conn, $content);
        $topic_sql = $topic_id === null ? "NULL" : (int) $topic_id;

        $sql = "INSERT INTO posts (user_id, title, content, topic_id) 
                VALUES ($user_id, '$title', '$content', $topic_sql)";
        if (mysqli_query($this->conn, $sql)) {
            return mysqli_insert_id($this->conn);
        }
        return false;
    }

    // Kiểm tra topic_id hợp lệ
    public function isValidTopic($topic_id)
    {
        $topic_id = (int) $topic_id;
        $sql = "SELECT id FROM topics WHERE id = $topic_id";
        $result = mysqli_query($this->conn, $sql);
        return mysqli_num_rows($result) > 0;
    }
}
