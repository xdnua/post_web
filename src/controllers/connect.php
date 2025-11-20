<?php
require_once __DIR__ . '/../config/database.php'; // Kết nối CSDL
require_once __DIR__ . '/../middleware/AuthMiddleware.php'; // Kiểm tra đăng nhập, xác thực người dùng
$baseUrl = '/posts'; // Thiết lập đường dẫn cơ sở cho các liên kết trong giao diện
?>