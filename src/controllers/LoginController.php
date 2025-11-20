<?php
session_start();
require_once __DIR__ . '/../models/LoginModel.php';

$error = '';
$model = new LoginModel($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu';
    } else {
        if ($model->login($username, $password)) {
            header('Location: /posts/index.php');
            exit();
        } else {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng';
        }
    }
}
?>