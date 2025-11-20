<?php
require_once __DIR__ . '/../models/NotificationModel.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$model = new NotificationModel($conn);

// Lấy danh sách thông báo
$notifications = $model->getUserNotifications($user_id);

// Đánh dấu tất cả đã đọc
$model->markAllAsRead($user_id);
?>