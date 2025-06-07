<?php
// Đảm bảo chỉ nhận file ảnh
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Không nhận được file ảnh']);
    exit;
}

$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowed)) {
    http_response_code(400);
    echo json_encode(['error' => 'Chỉ cho phép file ảnh jpg, jpeg, png, gif, webp']);
    exit;
}

$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}
$filename = uniqid('img_', true) . '.' . $ext;
$target = $uploadDir . $filename;
if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi khi lưu file ảnh']);
    exit;
}
// Đường dẫn trả về cho Quill (tùy baseUrl)
$baseUrl = '/posts';
$imageUrl = $baseUrl . '/uploads/' . $filename;
header('Content-Type: application/json');
echo json_encode(['success' => 1, 'url' => $imageUrl]); 