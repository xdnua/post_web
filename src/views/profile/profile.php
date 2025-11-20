<?php include __DIR__ . '/../../controllers/ProfileController.php'; ?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin tài khoản - Blog Chia Sẻ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/src/styles/global.css">
    <style>
        .avatar-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 20px auto;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid #ddd;
        }

        .avatar-container img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-upload-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            cursor: pointer;
        }

        .avatar-container:hover .avatar-upload-overlay {
            opacity: 1;
        }

        .avatar-upload-overlay input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
    </style>
</head>

<body style="padding-top: 60px;">
    <?php include __DIR__ . '/../../layout/navbar.php'; ?>

    <div class="container mt-5">
        <h2 class="mb-4">Thông tin tài khoản</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">

            <div class="form-group text-center mb-4">
                <label for="avatar">Ảnh đại diện:</label>
                <div class="avatar-container">
                    <img src="<?= $baseUrl ?>/src/assets/dist/avatars/<?php echo htmlspecialchars($user['avatar'] ?? 'default_avatar.png'); ?>"
                        alt="Ảnh đại diện">
                    <div class="avatar-upload-overlay">
                        <span>Chọn ảnh</span>
                        <input type="file" name="avatar" id="avatar" accept="image/*">
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="username" class="form-label">Tên đăng nhập:</label>
                <input type="text" class="form-control" id="username"
                    value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
            </div>

            <div class="mb-3">
                <label for="first_name" class="form-label">Tên:</label>
                <input type="text" class="form-control" id="first_name" name="first_name"
                    value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
            </div>

            <div class="mb-3">
                <label for="last_name" class="form-label">Họ:</label>
                <input type="text" class="form-control" id="last_name" name="last_name"
                    value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
            </div>

            <button type="submit" class="btn btn-primary">Cập nhật thông tin</button>
        </form>
    </div>

    <?php include __DIR__ . '/../../layout/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Xử lý sự kiện khi người dùng chọn ảnh đại diện mới
        document.getElementById('avatar').addEventListener('change', function (event) {
            const file = event.target.files[0]; // Lấy file được chọn từ input
            if (file) { // Kiểm tra nếu có file được chọn
                const reader = new FileReader(); // Tạo đối tượng FileReader để đọc file
                reader.onload = function (e) { // Khi đọc file thành công
                    document.querySelector('.avatar-container img').src = e.target.result; // Cập nhật src của ảnh đại diện
                }
                reader.readAsDataURL(file); // Đọc file dưới dạng Data URL
            }
        });
    </script>
</body>

</html>