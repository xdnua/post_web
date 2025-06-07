<?php $baseUrl = '/posts'; // Change this to your project's directory name ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lỗi 404 - Không tìm thấy trang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?=$baseUrl?>/global.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container mt-5 text-center">
    <h1>404</h1>
    <h2>Không tìm thấy trang</h2>
    <p>Trang bạn yêu cầu không tồn tại.</p>
    <a href="<?=$baseUrl?>/index.php" class="btn btn-primary">Quay về trang chủ</a>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 