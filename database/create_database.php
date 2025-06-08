<?php
$username = 'root';
$password = '';
$server = 'localhost';
$dbname = 'knowledge_sharing';

// Kết nối MySQL KHÔNG chỉ định database (vì chưa tạo)
$connect = new mysqli($server, $username, $password);

// Kiểm tra kết nối
if ($connect->connect_error) {
    die("Không kết nối: " . $connect->connect_error);
    exit();
}

// Tạo CSDL nếu chưa có
$csdl = "CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($connect->query($csdl) === TRUE) {
    echo "Tạo CSDL thành công.<br>";
} else {
    echo "Lỗi khi tạo CSDL: " . $connect->error;
}

// Đóng kết nối ban đầu
$connect->close();

// Bây giờ kết nối lại vào CSDL vừa tạo
$connect = new mysqli($server, $username, $password, $dbname);
if ($connect->connect_error) {
    die("Không kết nối CSDL: " . $connect->connect_error);
    exit();
}

echo "Đã kết nối tới CSDL, tiếp tục dòng code bên dưới đây.<br>";

// Tạo bảng users
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(255) NULL,
    last_name VARCHAR(255) NULL,
    avatar VARCHAR(255) NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    if ($connect->query($sql)===TRUE){
        echo "Tạo bảng user thành công!";
    } else {
        echo "Lỗi khi tạo bảng: " . $conn->error;
    }

// Tạo bảng topics
$sql1 = "CREATE TABLE IF NOT EXISTS topics (
    id INT(11) NOT NULL  PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    if ($connect->query($sql1)===TRUE){
        echo "Tạo bảng topics thành công!";
    } else {
        echo "Lỗi khi tạo bảng: " .$conn->error;
    }

// Tạo bảng posts
$sql2 = "CREATE TABLE IF NOT EXISTS posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    topic_id INT(11) NULL,
    user_id INT,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE SET NULL
    )";
    if ($connect->query($sql2)===TRUE){
        echo "Tạo bảng posts thành công!";
    } else {
        echo "Lỗi khi tạo bảng: " . $conn->error;
    }

// Tạo bảng comments   
$sql3 = "CREATE TABLE IF NOT EXISTS comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT,
    user_id INT,
    parent_id INT DEFAULT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE
    )";
    if ($connect->query($sql3)===TRUE){
        echo "Tạo bảng comments thành công!";
    } else {
        echo "Lỗi khi tạo bảng: " . $conn->error;
    }

// Tạo bảng likes    
$sql4 = "CREATE TABLE IF NOT EXISTS likes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT,
    user_id INT,
    type ENUM('like', 'dislike') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (post_id, user_id)
    )";
    if ($connect->query($sql4)===TRUE){
        echo "Tạo bảng likes thành công!";
    } else {
        echo "Lỗi khi tạo bảng: " . $conn->error;
    }

// Tạo bảng bookmarks (lưu bài viết yêu thích)
$sql5 = "CREATE TABLE IF NOT EXISTS bookmarks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_bookmark (user_id, post_id)
    )";
if ($connect->query($sql5)===TRUE){
    echo "Tạo bảng bookmarks thành công!";
} else {
    echo "Lỗi khi tạo bảng: " . $conn->error;
}

// Tạo bảng read_history (lưu lịch sử đọc bài viết)
$sql6 = "CREATE TABLE IF NOT EXISTS read_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    last_read_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_read (user_id, post_id)
    )";
if ($connect->query($sql6)===TRUE){
    echo "Tạo bảng read_history thành công!";
} else {
    echo "Lỗi khi tạo bảng: " . $conn->error;
}

echo "Cơ sở dữ liệu và bảng đã tạo thành công với ràng buộc hợp lý.";
// Đóng kết nối 
$connect->close();
?>