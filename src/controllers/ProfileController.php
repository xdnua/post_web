<?php
if (session_status() === PHP_SESSION_NONE)
    session_start();
require_once __DIR__ . '/../models/ProfileModel.php';

if (!isLoggedIn()) {
    header('Location: /posts/src/views/auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$model = new ProfileModel($conn);
$user = $model->getUserById($user_id);

$error = '';
$success = '';

// Xử lý POST cập nhật profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $avatar = $user['avatar'];

    // Upload avatar nếu có
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
        $allowed_types = ['jpg' => 'image/jpg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'png' => 'image/png'];
        $file_name = $_FILES['avatar']['name'];
        $file_type = $_FILES['avatar']['type'];
        $file_size = $_FILES['avatar']['size'];
        $temp_path = $_FILES['avatar']['tmp_name'];
        $ext = pathinfo($file_name, PATHINFO_EXTENSION);

        if (!array_key_exists($ext, $allowed_types))
            $error = 'Định dạng file không hợp lệ.';
        elseif ($file_size > 5 * 1024 * 1024)
            $error = 'Kích thước file quá lớn, tối đa 5MB.';
        elseif (!in_array($file_type, $allowed_types))
            $error = 'Định dạng MIME không hợp lệ.';

        if (!$error) {
            $new_file_name = uniqid() . '.' . $ext;
            $upload_dir = __DIR__ . '/../assets/dist/avatars/';
            if (!is_dir($upload_dir))
                mkdir($upload_dir, 0777, true);
            if (move_uploaded_file($temp_path, $upload_dir . $new_file_name)) {
                // Xóa avatar cũ nếu không phải mặc định
                if (!empty($user['avatar']) && $user['avatar'] != 'default_avatar.png') {
                    $old_avatar_path = $upload_dir . $user['avatar'];
                    if (file_exists($old_avatar_path))
                        unlink($old_avatar_path);
                }
                $avatar = $new_file_name;
                $success = 'Cập nhật ảnh đại diện thành công!';
            } else
                $error = 'Lỗi khi tải lên ảnh đại diện.';
        }
    }

    // Nếu không lỗi thì cập nhật thông tin
    if (!$error) {
        if ($model->updateUser($user_id, $first_name, $last_name, $avatar)) {
            $success = $success ?: 'Cập nhật thông tin tài khoản thành công!';
            $_SESSION['success_message'] = $success;
            header('Location: profile.php');
            exit();
        } else
            $error = 'Lỗi khi cập nhật cơ sở dữ liệu.';
    }

    if ($error)
        $_SESSION['error_message'] = $error;
}

// Lấy thông báo từ session
$error = $_SESSION['error_message'] ?? '';
$success = $_SESSION['success_message'] ?? '';
unset($_SESSION['error_message'], $_SESSION['success_message']);
?>