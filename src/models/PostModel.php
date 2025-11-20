<?php
class PostModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function countPosts($search, $topic_id)
    {
        $conditions = [];
        if (!empty($search)) {
            $search = mysqli_real_escape_string($this->conn, $search);
            $conditions[] = "(p.title LIKE '%$search%' OR p.content LIKE '%$search%' OR t.name LIKE '%$search%')";
        }
        if (!empty($topic_id)) {
            $conditions[] = "p.topic_id = " . (int) $topic_id;
        }

        $where = empty($conditions) ? "" : " WHERE " . implode(" AND ", $conditions);

        $sql = "SELECT COUNT(*) as total FROM posts p LEFT JOIN topics t ON p.topic_id = t.id $where";
        $rs = mysqli_query($this->conn, $sql);
        return mysqli_fetch_assoc($rs)['total'];
    }

    public function getPosts($search, $topic_id, $limit, $offset)
    {
        $conditions = [];
        if (!empty($search)) {
            $search = mysqli_real_escape_string($this->conn, $search);
            $conditions[] = "(p.title LIKE '%$search%' OR p.content LIKE '%$search%' OR t.name LIKE '%$search%')";
        }
        if (!empty($topic_id)) {
            $conditions[] = "p.topic_id = " . (int) $topic_id;
        }

        $where = empty($conditions) ? " WHERE 1=1" : " WHERE " . implode(" AND ", $conditions);

        $sql = "SELECT p.*, u.username, t.name AS topic_name,
            (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND type='like') as like_count,
            (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND type='dislike') as dislike_count,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
        FROM posts p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN topics t ON p.topic_id = t.id
        $where
        ORDER BY p.created_at DESC
        LIMIT $limit OFFSET $offset";

        return mysqli_query($this->conn, $sql);
    }
}
?>