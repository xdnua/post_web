<?php
session_start();
require_once __DIR__ . '/connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Kiểm tra xem đã nhập đủ tên đăng nhập và mật khẩu chưa
    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu';
    } else {
        // Gọi hàm login kiểm tra tài khoản, nếu đúng thì chuyển về trang chủ
        if (login($username, $password)) {
            header('Location: /posts/index.php');
            exit();
        } else {
            // Sai tài khoản hoặc mật khẩu
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng';
        }
    }
}
?>