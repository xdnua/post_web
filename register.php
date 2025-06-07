<?php
require_once 'config/database.php'; // Kết nối tới cơ sở dữ liệu
require_once 'auth/auth.php'; // Xác thực tài khoản

$error = '';
$success = '';

// Xử lý khi người dùng gửi form đăng ký
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = $_POST['email'] ?? '';
    
    // Kiểm tra các trường không được để trống
    if (empty($username) || empty($password) || empty($confirm_password) || empty($email)) {
        $error = 'Vui lòng điền đầy đủ tất cả các trường';
    } elseif ($password !== $confirm_password) {
        // Kiểm tra xác nhận mật khẩu
        $error = 'Mật khẩu xác nhận không khớp';
    } elseif (strlen($password) < 6) {
        // Kiểm tra độ dài mật khẩu tối thiểu
        $error = 'Mật khẩu phải dài ít nhất 6 ký tự';
    } else {
        // Kiểm tra tài khoản hoặc email đã tồn tại chưa
        $check_query = "SELECT * FROM users WHERE username = '$username' OR email = '$email'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = 'Tên đăng nhập hoặc email đã tồn tại';
        } else {
            // Nếu hợp lệ, tiến hành đăng ký tài khoản mới
            if (register($username, $password, $email)) {
                // Đăng ký thành công, hiển thị thông báo màu xanh lá nổi bật ngay bên dưới form, sau 100ms chuyển sang đăng nhập
                $success = 'Đăng ký thành công! Đang chuyển sang trang đăng nhập...';
                echo '<script>setTimeout(function(){ window.location.href = "login.php?registered=1"; }, 100);</script>';
            } else {
                $error = 'Đăng ký thất bại. Vui lòng thử lại.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Knowledge Sharing Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Register</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <p class="form-label " style="color:red; padding-top: 7px;">*Mật khẩu phải dài ít nhất 6 ký tự</p>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Register</button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-3">
                            <p>Already have an account? <a href="login.php">Login here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php include 'footer.php'; ?>