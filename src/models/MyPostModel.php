<?php
require_once __DIR__ . '/../controllers/connect.php';

class MyPostModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Đếm tổng số bài viết của user (có tìm kiếm)
    public function countUserPosts($user_id, $search = '')
    {
        $condition = " WHERE user_id = " . (int) $user_id;
        if ($search) {
            $escaped = mysqli_real_escape_string($this->conn, $search);
            $condition .= " AND (title LIKE '%$escaped%' OR content LIKE '%$escaped%')";
        }
        $sql = "SELECT COUNT(*) as total FROM posts" . $condition;
        $result = mysqli_query($this->conn, $sql);
        return mysqli_fetch_assoc($result)['total'] ?? 0;
    }

    // Lấy danh sách bài viết của user (có phân trang và tìm kiếm)
    public function getUserPosts($user_id, $limit, $offset, $search = '')
    {
        $condition = " WHERE user_id = " . (int) $user_id;
        if ($search) {
            $escaped = mysqli_real_escape_string($this->conn, $search);
            $condition .= " AND (title LIKE '%$escaped%' OR content LIKE '%$escaped%')";
        }

        $sql = "SELECT p.*,
                    (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND type = 'like') as like_count,
                    (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND type = 'dislike') as dislike_count
                FROM posts p
                $condition
                ORDER BY created_at DESC
                LIMIT $limit OFFSET $offset";
        return mysqli_query($this->conn, $sql);
    }
}
