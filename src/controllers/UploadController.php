<?php
// Đảm bảo chỉ nhận file ảnh
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);  // Trả về mã lỗi 400 nếu không có file hoặc có lỗi khi tải lên
    echo json_encode(['error' => 'Không nhận được file ảnh']);
    exit;
}
// Kiểm tra kích thước file (tối đa 5MB)
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION)); 
if (!in_array($ext, $allowed)) { // Kiểm tra định dạng file
    http_response_code(400);
    echo json_encode(['error' => 'Chỉ cho phép file ảnh jpg, jpeg, png, gif, webp']);
    exit;
}

$uploadDir = __DIR__ . '/uploads/'; // Thư mục lưu trữ ảnh
if (!is_dir($uploadDir)) { // Kiểm tra và tạo thư mục nếu chưa tồn tại
    mkdir($uploadDir, 0777, true); 
}
$filename = uniqid('img_', true) . '.' . $ext; // Tạo tên file duy nhất để tránh trùng lặp
$target = $uploadDir . $filename; // Đường dẫn đầy đủ đến file sẽ lưu
if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) { // nếu không thể di chuyển file tải lên thì trả về lỗi
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi khi lưu file ảnh']);
    exit;
}
// Đường dẫn trả về cho Quill (tùy baseUrl)
$baseUrl = '/posts';
$imageUrl = $baseUrl . '/uploads/' . $filename;
header('Content-Type: application/json');
echo json_encode(['success' => 1, 'url' => $imageUrl]); 