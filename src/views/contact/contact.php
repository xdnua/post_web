<?php
include __DIR__ . "/../../controllers/connect.php";
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liên hệ - Blog Chia Sẻ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/src/styles/global.css">
</head>

<body style="padding-top: 60px;">
    <?php include __DIR__ . '/../../layout/navbar.php'; ?>

    <div class="container mt-5">
        <h2>Liên hệ với chúng tôi</h2>
        <p>Bạn có thể liên hệ với chúng tôi qua các thông tin dưới đây:</p>
        <ul>
            <li><strong>Điện thoại:</strong> 0876 176 706</li>
            <li><strong>Email:</strong> anhhongchemgio@gmail.com</li>
            <li><strong>Địa chỉ:</strong> Phù Yên, Phúc Lâm, Mỹ Đức, Hà Nội</li>
        </ul>
        <div>
            <a href="https://www.facebook.com/anh.hong.594371" class="me-2 text-primary"><i class="bi bi-facebook"
                    style="font-size: 1.5rem;"></i></a>
            <a href="https://www.tiktok.com/@aht_2811?lang=vi-VN" class="me-2 text-info"><i class="bi bi-tiktok"
                    style="font-size: 1.5rem;"></i></a>
            <a href="https://www.instagram.com/hongvu2811/" class="text-danger"><i class="bi bi-instagram"
                    style="font-size: 1.5rem;"></i></a>
        </div>
        <!-- Thêm form liên hệ hoặc thông tin chi tiết khác tại đây -->
    </div>

    <?php include __DIR__ . '/../../layout/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>