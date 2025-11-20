<?php
require_once __DIR__ . '/../controllers/connect.php';

class PostModel
{
    private $conn;
    private $limit = 6;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function countPosts($search_term = '', $topic_id = null)
    {
        $conditions = [];
        if (!empty($search_term)) {
            $search_term = mysqli_real_escape_string($this->conn, $search_term);
            $conditions[] = "(p.title LIKE '%$search_term%' OR p.content LIKE '%$search_term%' OR t.name LIKE '%$search_term%')";
        }
        if ($topic_id !== null) {
            $topic_id = (int) $topic_id;
            $conditions[] = "p.topic_id = $topic_id";
        }

        $where = '';
        if ($conditions) {
            $where = " WHERE " . implode(' AND ', $conditions);
        }

        $sql = "SELECT COUNT(*) as total FROM posts p LEFT JOIN topics t ON p.topic_id = t.id $where";
        $result = mysqli_query($this->conn, $sql);
        $row = mysqli_fetch_assoc($result);
        return (int) $row['total'];
    }

    public function getPosts($page = 1, $search_term = '', $topic_id = null)
    {
        $offset = ($page - 1) * $this->limit;

        $conditions = [];
        if (!empty($search_term)) {
            $search_term = mysqli_real_escape_string($this->conn, $search_term);
            $conditions[] = "(p.title LIKE '%$search_term%' OR p.content LIKE '%$search_term%' OR t.name LIKE '%$search_term%')";
        }
        if ($topic_id !== null) {
            $topic_id = (int) $topic_id;
            $conditions[] = "p.topic_id = $topic_id";
        }

        $where = '';
        if ($conditions)
            $where = " WHERE " . implode(' AND ', $conditions);

        $sql = "SELECT p.*, u.username, t.name as topic_name,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND type = 'like') as like_count,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND type = 'dislike') as dislike_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
                FROM posts p
                JOIN users u ON p.user_id = u.id
                LEFT JOIN topics t ON p.topic_id = t.id
                $where
                ORDER BY p.created_at DESC
                LIMIT $this->limit OFFSET $offset";

        return mysqli_query($this->conn, $sql);
    }

    public function getTotalPages($total_posts)
    {
        return ceil($total_posts / $this->limit);
    }
}
?>