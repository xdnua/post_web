<?php
require_once 'config/database.php';
require_once 'auth/auth.php';
$baseUrl = '/posts'; // Đổi thành tên thư mục dự án của bạn
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giới thiệu - Blog Chia Sẻ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?=$baseUrl?>/global.css">
</head>
<body style="padding-top: 60px;">
<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <h2>Giới thiệu về Blog Chia Sẻ</h2>
    <p>Đây là trang giới thiệu về website của chúng tôi. Chúng tôi mong muốn xây dựng một cộng đồng chia sẻ kiến thức và kinh nghiệm hữu ích.</p>
    <!-- Thêm nội dung giới thiệu chi tiết tại đây -->
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 