<?php
require_once __DIR__ . '/../controllers/connect.php';

class LoginModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Kiểm tra thông tin đăng nhập
    public function login($username, $password)
    {
        $username = mysqli_real_escape_string($this->conn, $username);

        $sql = "SELECT id, username, password FROM users WHERE username = ? LIMIT 1";
        $stmt = mysqli_prepare($this->conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if ($user && password_verify($password, $user['password'])) {
                // Lưu thông tin user vào session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                return true;
            }
        }
        return false;
    }
}
