<?php
// Giữ trạng thái đăng nhập trong 7 ngày (nếu không đăng xuất)
$lifetime = 60 * 60 * 24 * 7;

// Nếu phiên chưa được khởi tạo thì cấu hình thời gian lưu đăng nhập và khởi tạo session
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => $lifetime, 
        'path' => '/', // Đường dẫn áp dụng cho toàn bộ trang web
        'httponly' => true, // Chỉ cho phép truy cập qua trình duyệt, tăng bảo mật
        'secure' => isset($_SERVER['HTTPS']), // Chỉ lưu đăng nhập khi truy cập bằng đường dẫn có ổ khóa (https)
        'samesite' => 'Lax' // Giúp hạn chế nguy cơ bị đánh cắp đăng nhập từ trang web khác
    ]);
    session_start(); 
}

// Kiểm tra người dùng đã đăng nhập hay chưa
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Kiểm tra người dùng có phải là admin không
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Bắt buộc đăng nhập, nếu chưa đăng nhập thì chuyển hướng về trang đăng nhập
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit();
    }
}

// Bắt buộc là admin, nếu không phải thì chuyển hướng về trang chủ
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: /index.php');
        exit();
    }
}

// Xử lý đăng nhập: kiểm tra username và password, nếu đúng thì lưu thông tin vào session
function login($username, $password) {
    global $conn; // Kết nối đến cơ sở dữ liệu
    
    $username = mysqli_real_escape_string($conn, $username);
    $query = "SELECT id, username, password, role FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $query); 
    
    if ($result && mysqli_num_rows($result) > 0) { 
        $user = mysqli_fetch_assoc($result); 
        if (password_verify($password, $user['password'])) { 
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            return true; 
        }
    }
    return false;
}

// Xử lý đăng ký tài khoản mới
function register($username, $password, $email) {
    global $conn; 
    
    $username = mysqli_real_escape_string($conn, $username); 
    $email = mysqli_real_escape_string($conn, $email);
    $hashed_password = password_hash($password, PASSWORD_DEFAULT); // Mã hóa mật khẩu
    
    $query = "INSERT INTO users (username, password, email) VALUES ('$username', '$hashed_password', '$email')";
    return mysqli_query($conn, $query);
}

// Đăng xuất: hủy session và chuyển về trang chủ
function logout() {
    session_destroy();
    header('Location: /posts/');
    exit();
}
?>