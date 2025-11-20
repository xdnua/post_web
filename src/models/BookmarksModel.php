<?php
require_once __DIR__ . '/../controllers/connect.php';

class BookmarksModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Lấy danh sách bài viết đã lưu của người dùng
    public function getUserBookmarks($user_id)
    {
        $sql = "SELECT p.*, sp.created_at,
                    (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND type = 'like') AS like_count,
                    (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND type = 'dislike') AS dislike_count
                FROM bookmarks sp
                JOIN posts p ON sp.post_id = p.id
                WHERE sp.user_id = ?
                ORDER BY sp.created_at DESC";
        $stmt = mysqli_prepare($this->conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $bookmarks = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $bookmarks[] = $row;
            }
            mysqli_stmt_close($stmt);
            return $bookmarks;
        }
        return [];
    }

    // Xóa bookmark
    public function deleteBookmark($post_id, $user_id)
    {
        $stmt = mysqli_prepare($this->conn, "DELETE FROM bookmarks WHERE post_id = ? AND user_id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ii', $post_id, $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            return true;
        }
        return false;
    }
}
