<?php
require_once __DIR__ . '/../controllers/connect.php';

class ProfileModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getUserById($user_id)
    {
        $sql = "SELECT username, first_name, last_name, avatar FROM users WHERE id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $user;
    }

    public function updateUser($user_id, $first_name, $last_name, $avatar)
    {
        $sql = "UPDATE users SET first_name = ?, last_name = ?, avatar = ? WHERE id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        if (!$stmt)
            return false;
        mysqli_stmt_bind_param($stmt, 'sssi', $first_name, $last_name, $avatar, $user_id);
        $res = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $res;
    }
}
?>