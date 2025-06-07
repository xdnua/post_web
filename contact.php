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
    <title>Liên hệ - Blog Chia Sẻ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?=$baseUrl?>/global.css">
</head>
<body style="padding-top: 60px;">
<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <h2>Liên hệ với chúng tôi</h2>
    <p>Bạn có thể liên hệ với chúng tôi qua các thông tin dưới đây:</p>
    <ul>
        <li><strong>Điện thoại:</strong> 0976 176 706</li>
        <li><strong>Email:</strong> anhhongthuonggo@gmail.com</li>
        <li><strong>Địa chỉ:</strong> Phú Yên, Phúc Lâm, Mỹ Đức, Hà Nội</li>
    </ul>
    <!-- Thêm form liên hệ hoặc thông tin chi tiết khác tại đây -->
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 