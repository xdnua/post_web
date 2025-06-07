<?php
// Set session cookie lifetime to 7 days
$lifetime = 60 * 60 * 24 * 7;

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path' => '/',
        'httponly' => true,
        'secure' => isset($_SERVER['HTTPS']),
        'samesite' => 'Lax'
    ]);
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit();
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: /index.php');
        exit();
    }
}

function login($username, $password) {
    global $conn;
    
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

function register($username, $password, $email) {
    global $conn;
    
    $username = mysqli_real_escape_string($conn, $username);
    $email = mysqli_real_escape_string($conn, $email);
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $query = "INSERT INTO users (username, password, email) VALUES ('$username', '$hashed_password', '$email')";
    return mysqli_query($conn, $query);
}

function logout() {
    session_destroy();
    header('Location: /posts/');
    exit();
}
?> 