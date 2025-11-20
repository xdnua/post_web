<?php
require_once __DIR__ . '/../controllers/connect.php';

class AdminUserModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Lấy danh sách người dùng (có phân trang và tìm kiếm)
    public function getUsers($limit, $offset, $search = '')
    {
        $condition = '';
        if ($search) {
            $escaped = mysqli_real_escape_string($this->conn, $search);
            $condition = " WHERE username LIKE '%$escaped%' OR email LIKE '%$escaped%'";
        }
        $sql = "SELECT id, username, email, role, created_at, first_name, last_name, avatar
                FROM users
                $condition
                ORDER BY created_at DESC
                LIMIT $limit OFFSET $offset";
        return mysqli_query($this->conn, $sql);
    }

    // Đếm tổng số người dùng (có tìm kiếm)
    public function countUsers($search = '')
    {
        $condition = '';
        if ($search) {
            $escaped = mysqli_real_escape_string($this->conn, $search);
            $condition = " WHERE username LIKE '%$escaped%' OR email LIKE '%$escaped%'";
        }
        $sql = "SELECT COUNT(*) as total FROM users $condition";
        $result = mysqli_query($this->conn, $sql);
        return mysqli_fetch_assoc($result)['total'] ?? 0;
    }

    // Xóa người dùng
    public function deleteUser($id, $current_user_id)
    {
        $id = (int) $id;
        if ($id === $current_user_id)
            return false; // Không được tự xóa chính mình
        $sql = "DELETE FROM users WHERE id = $id";
        return mysqli_query($this->conn, $sql);
    }

    // Cập nhật vai trò người dùng
    public function updateRole($id, $role, $current_user_id)
    {
        $id = (int) $id;
        if ($id === $current_user_id)
            return false; // Không được tự đổi role
        $role = mysqli_real_escape_string($this->conn, $role);
        $sql = "UPDATE users SET role = '$role' WHERE id = $id";
        return mysqli_query($this->conn, $sql);
    }
}
