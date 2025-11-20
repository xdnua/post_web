<?php
require_once __DIR__ . '/../controllers/connect.php';

class AdminPostModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Lấy tổng số bài viết (có search)
    public function countPosts($search = '')
    {
        $condition = '';
        if ($search) {
            $escaped = mysqli_real_escape_string($this->conn, $search);
            $condition = " WHERE p.title LIKE '%$escaped%' OR p.content LIKE '%$escaped%' OR t.name LIKE '%$escaped%'";
        }
        $sql = "SELECT COUNT(*) as total FROM posts p LEFT JOIN topics t ON p.topic_id = t.id" . $condition;
        $result = mysqli_query($this->conn, $sql);
        return mysqli_fetch_assoc($result)['total'] ?? 0;
    }

    // Lấy danh sách bài viết theo trang
    public function getPosts($limit, $offset, $search = '')
    {
        $condition = '';
        if ($search) {
            $escaped = mysqli_real_escape_string($this->conn, $search);
            $condition = " WHERE p.title LIKE '%$escaped%' OR p.content LIKE '%$escaped%' OR t.name LIKE '%$escaped%'";
        } else {
            $condition = " WHERE 1=1";
        }

        $sql = "SELECT p.*, u.username, t.name as topic_name,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND type = 'like') as like_count,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND type = 'dislike') as dislike_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
                FROM posts p 
                JOIN users u ON p.user_id = u.id 
                LEFT JOIN topics t ON p.topic_id = t.id
                $condition
                ORDER BY p.created_at DESC
                LIMIT $limit OFFSET $offset";

        return mysqli_query($this->conn, $sql);
    }

    // Xóa bài viết và các ảnh liên quan
    public function deletePost($post_id)
    {
        $post_id = (int) $post_id;
        $get_content = mysqli_query($this->conn, "SELECT content FROM posts WHERE id = $post_id");
        $row = mysqli_fetch_assoc($get_content);
        if ($row && !empty($row['content'])) {
            if (preg_match_all('/src="(.*?)"/', $row['content'], $matches)) {
                foreach ($matches[1] as $img_url) {
                    $img_path = $_SERVER['DOCUMENT_ROOT'] . parse_url($img_url, PHP_URL_PATH);
                    if (strpos($img_path, '/uploads/') !== false && file_exists($img_path)) {
                        @unlink($img_path);
                    }
                }
            }
        }

        return mysqli_query($this->conn, "DELETE FROM posts WHERE id = $post_id");
    }
}
