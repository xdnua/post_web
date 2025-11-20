<?php
require_once __DIR__ . '/../controllers/connect.php';

class RegisterModal
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function isUserExist($username, $email)
    {
        $username = mysqli_real_escape_string($this->conn, $username);
        $email = mysqli_real_escape_string($this->conn, $email);
        $sql = "SELECT id FROM users WHERE username='$username' OR email='$email'";
        $result = mysqli_query($this->conn, $sql);
        return mysqli_num_rows($result) > 0;
    }

    public function registerUser($username, $password, $email)
    {
        $username = mysqli_real_escape_string($this->conn, $username);
        $email = mysqli_real_escape_string($this->conn, $email);
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password, email) VALUES ('$username', '$password_hash', '$email')";
        return mysqli_query($this->conn, $sql);
    }
}
?>