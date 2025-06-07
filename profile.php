<?php
// Kiểm tra session, nếu chưa có thì khởi tạo
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/database.php'; // Kết nối tới cơ sở dữ liệu
require_once 'auth/auth.php'; // Nạp các hàm xác thực tài khoản
$baseUrl = '/posts'; // Đường dẫn gốc của dự án (cần chỉnh lại nếu đổi tên thư mục)

// Nếu chưa đăng nhập thì chuyển hướng về trang đăng nhập
if (!isLoggedIn()) {
    header('Location: ' . $baseUrl . '/login.php');
    exit;
}

$user_id = $_SESSION['user_id']; // Lấy id người dùng hiện tại từ session

// Lấy thông tin user từ database (dùng prepared statement để tránh SQL injection)
$sql = "SELECT username, first_name, last_name, avatar FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Xử lý khi người dùng gửi form cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']); // Lấy tên mới từ form
    $last_name = trim($_POST['last_name']);   // Lấy họ mới từ form
    $avatar = $user['avatar']; // Mặc định giữ nguyên avatar cũ

    // Xử lý upload ảnh đại diện nếu có chọn file
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        // Kiểm tra định dạng file hợp lệ
        $allowed_types = ['jpg' => 'image/jpg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'png' => 'image/png'];
        $file_name = $_FILES['avatar']['name'];
        $file_type = $_FILES['avatar']['type'];
        $file_size = $_FILES['avatar']['size'];
        $temp_path = $_FILES['avatar']['tmp_name'];

        $ext = pathinfo($file_name, PATHINFO_EXTENSION);
        if (!array_key_exists($ext, $allowed_types)) {
            $_SESSION['error_message'] = 'Lỗi: Vui lòng chọn định dạng file ảnh hợp lệ (JPG, JPEG, GIF, PNG).';
        }
        // Kiểm tra kích thước file (tối đa 5MB)
        $maxsize = 5 * 1024 * 1024;
        if ($file_size > $maxsize) {
            $_SESSION['error_message'] = 'Lỗi: Kích thước file quá lớn, tối đa 5MB.';
        }
        // Kiểm tra mime type
        if (!in_array($file_type, $allowed_types)) {
            $_SESSION['error_message'] = 'Lỗi: Định dạng file không hợp lệ.';
        }
        // Nếu không có lỗi thì tiến hành upload
        if (!isset($_SESSION['error_message'])) {
            $new_file_name = uniqid() . '.' . $ext; // Tạo tên file mới duy nhất
            $upload_path = __DIR__ . '/dist/avatars/' . $new_file_name;
            if (!is_dir(__DIR__ . '/dist/avatars/')) {
                 mkdir(__DIR__ . '/dist/avatars/', 0777, true); // Tạo thư mục nếu chưa có
            }
            if (move_uploaded_file($temp_path, $upload_path)) {
                 // Nếu upload thành công, xóa avatar cũ nếu không phải mặc định
                 if (!empty($user['avatar']) && $user['avatar'] != 'default_avatar.png') {
                    $old_avatar_path = __DIR__ . '/dist/avatars/' . $user['avatar'];
                    if (file_exists($old_avatar_path)) {
                        unlink(str_replace('\\', '/', $old_avatar_path));
                    }
                 }
                $avatar = $new_file_name;
                $_SESSION['success_message'] = 'Cập nhật ảnh đại diện thành công!';
            } else {
                $_SESSION['error_message'] = 'Lỗi khi tải lên ảnh đại diện.';
            }
        }
    } else if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] != 0) {
         // Nếu upload lỗi (trừ trường hợp không chọn file)
         if ($_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
              $_SESSION['error_message'] = 'Lỗi tải file: Mã lỗi ' . $_FILES['avatar']['error'];
         }
    }

    // Nếu không có lỗi thì cập nhật thông tin user vào database
    if (empty($_SESSION['error_message'])) {
        $update_sql = "UPDATE users SET first_name = ?, last_name = ?, avatar = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        if ($update_stmt) {
            mysqli_stmt_bind_param($update_stmt, 'sssi', $first_name, $last_name, $avatar, $user_id);
            if (mysqli_stmt_execute($update_stmt)) {
                // Thành công: chuyển hướng lại trang profile (theo nguyên tắc POST/Redirect/GET)
                $_SESSION['success_message'] = $_SESSION['success_message'] ?? 'Cập nhật thông tin tài khoản thành công!';
                header('Location: ' . $baseUrl . '/profile.php');
                exit();
            } else {
                $_SESSION['error_message'] = 'Lỗi khi cập nhật thông tin tài khoản.';
            }
            mysqli_stmt_close($update_stmt);
        } else {
            $_SESSION['error_message'] = 'Lỗi chuẩn bị câu lệnh cập nhật cơ sở dữ liệu.';
        }
    }
}

// Lấy thông báo lỗi/thành công từ session (nếu có), sau đó xóa khỏi session để tránh lặp lại
$error = $_SESSION['error_message'] ?? '';
$success = $_SESSION['success_message'] ?? '';
unset($_SESSION['error_message']);
unset($_SESSION['success_message']);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin tài khoản - Blog Chia Sẻ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?=$baseUrl?>/global.css">
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
<?php include 'navbar.php'; ?>

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
                <img src="<?=$baseUrl?>/dist/avatars/<?php echo htmlspecialchars($user['avatar'] ?? 'default_avatar.png'); ?>" alt="Ảnh đại diện">
                <div class="avatar-upload-overlay">
                    <span>Chọn ảnh</span>
                    <input type="file" name="avatar" id="avatar" accept="image/*">
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label for="username" class="form-label">Tên đăng nhập:</label>
            <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
        </div>

        <div class="mb-3">
            <label for="first_name" class="form-label">Tên:</label>
            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
        </div>

        <div class="mb-3">
            <label for="last_name" class="form-label">Họ:</label>
            <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
        </div>

        <button type="submit" class="btn btn-primary">Cập nhật thông tin</button>
    </form>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('avatar').addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.querySelector('.avatar-container img').src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });
</script>
</body>
</html>