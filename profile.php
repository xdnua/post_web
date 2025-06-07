<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/database.php';
require_once 'auth/auth.php';
$baseUrl = '/posts'; // Đổi thành tên thư mục dự án của bạn

// Redirect if user is not logged in
if (!isLoggedIn()) {
    header('Location: ' . $baseUrl . '/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$sql = "SELECT username, first_name, last_name, avatar FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $avatar = $user['avatar']; // Keep current avatar by default

    // Handle avatar upload
    error_log("Avatar upload attempt.");
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        error_log("File received. Error code: " . $_FILES['avatar']['error']);
        error_log("File details: name=" . $_FILES['avatar']['name'] . ", type=" . $_FILES['avatar']['type'] . ", size=" . $_FILES['avatar']['size'] . ", tmp_name=" . $_FILES['avatar']['tmp_name']);

        $allowed_types = ['jpg' => 'image/jpg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'png' => 'image/png'];
        $file_name = $_FILES['avatar']['name'];
        $file_type = $_FILES['avatar']['type'];
        $file_size = $_FILES['avatar']['size'];
        $temp_path = $_FILES['avatar']['tmp_name'];

        // Verify file extension
        $ext = pathinfo($file_name, PATHINFO_EXTENSION);
        if (!array_key_exists($ext, $allowed_types)) {
            $_SESSION['error_message'] = 'Lỗi: Vui lòng chọn định dạng file ảnh hợp lệ (JPG, JPEG, GIF, PNG).';
            error_log("File extension error: " . $ext);
        }

        // Verify file size - max 5MB
        $maxsize = 5 * 1024 * 1024;
        if ($file_size > $maxsize) {
            $_SESSION['error_message'] = 'Lỗi: Kích thước file quá lớn, tối đa 5MB.';
            error_log("File size error: " . $file_size);
        }

        // Verify MIME type
        if (!in_array($file_type, $allowed_types)) {
            $_SESSION['error_message'] = 'Lỗi: Định dạng file không hợp lệ.';
            error_log("File MIME type error: " . $file_type);
        }

        // Process upload if no errors
        if (!isset($_SESSION['error_message'])) {
            $new_file_name = uniqid() . '.' . $ext; // Generate a unique filename
            $upload_path = __DIR__ . '/dist/avatars/' . $new_file_name;

            error_log("New file name: " . $new_file_name);
            error_log("Upload path: " . $upload_path);

            if (!is_dir(__DIR__ . '/dist/avatars/')) {
                 mkdir(__DIR__ . '/dist/avatars/', 0777, true);
                 error_log("Avatars directory created.");
            }

            if (move_uploaded_file($temp_path, $upload_path)) {
                 error_log("File moved successfully.");
                 // Delete old avatar if it exists and is not the default
                 if (!empty($user['avatar']) && $user['avatar'] != 'default_avatar.png') {
                    $old_avatar_path = __DIR__ . '/dist/avatars/' . $user['avatar'];
                    error_log("Attempting to delete old avatar: " . $old_avatar_path);
                    if (file_exists($old_avatar_path)) {
                        unlink(str_replace('\\', '/', $old_avatar_path)); // Use forward slashes for path
                        error_log("Old avatar deleted.");
                    }
                 }
                $avatar = $new_file_name;
                $_SESSION['success_message'] = 'Cập nhật ảnh đại diện thành công!'; // Avatar update success is part of main success
            } else {
                $_SESSION['error_message'] = 'Lỗi khi tải lên ảnh đại diện.';
                error_log("Failed to move uploaded file.");
            }
        }
    } else if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] != 0) {
         error_log("File upload error. Error code: " . $_FILES['avatar']['error']);
         // Only set error message for upload errors other than UPLOAD_ERR_NO_FILE (code 4)
         if ($_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
              $_SESSION['error_message'] = 'Lỗi tải file: Mã lỗi ' . $_FILES['avatar']['error'];
         }
    } else {
        error_log("No avatar file uploaded.");
    }

    // Update user data in database if no errors
    if (empty($_SESSION['error_message'])) {
        error_log("No errors, attempting database update.");
        error_log("Avatar value for update: " . $avatar);
        $update_sql = "UPDATE users SET first_name = ?, last_name = ?, avatar = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        
        if ($update_stmt) {
            mysqli_stmt_bind_param($update_stmt, 'sssi', $first_name, $last_name, $avatar, $user_id);

            if (mysqli_stmt_execute($update_stmt)) {
                 error_log("Database update successful.");
                 // Success - Redirect using PRG pattern
                $_SESSION['success_message'] = $_SESSION['success_message'] ?? 'Cập nhật thông tin tài khoản thành công!'; // Keep avatar success message if set
                header('Location: ' . $baseUrl . '/profile.php');
                exit();
            } else {
                $_SESSION['error_message'] = 'Lỗi khi cập nhật thông tin tài khoản.'; // Overwrite or set error
                error_log("Database update failed: " . mysqli_error($conn));
            }
            mysqli_stmt_close($update_stmt);
        } else {
            $_SESSION['error_message'] = 'Lỗi chuẩn bị câu lệnh cập nhật cơ sở dữ liệu.';
            error_log("Database update prepared statement failed: " . mysqli_error($conn));
        }
    }
}

// Check for and display messages from session
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