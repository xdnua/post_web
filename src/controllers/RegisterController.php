<?php
require_once __DIR__ . '/../models/RegisterModal.php';

$error = '';
$success = '';

$model = new RegisterModal($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = trim($_POST['email'] ?? '');

    // Validate
    if (!$username || !$password || !$confirm_password || !$email) {
        $error = 'Vui lòng điền đầy đủ tất cả các trường';
    } elseif ($password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải dài ít nhất 6 ký tự';
    } elseif ($model->isUserExist($username, $email)) {
        $error = 'Tên đăng nhập hoặc email đã tồn tại';
    } else {
        if ($model->registerUser($username, $password, $email)) {
            $success = 'Đăng ký thành công! Đang chuyển sang trang đăng nhập...';
            $_SESSION['success_message'] = $success;
            header('Refresh: 0.1; URL=login.php?registered=1');
            exit();
        } else {
            $error = 'Đăng ký thất bại. Vui lòng thử lại.';
        }
    }
}
?>