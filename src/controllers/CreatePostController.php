<?php
require_once __DIR__ . '/../models/CreatePostModel.php';
requireLogin();

$error = '';
$success = '';
$model = new CreatePostModel($conn);

// Lấy danh sách chủ đề
$topics = $model->getTopics();

// Xử lý khi gửi form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $topic_id = $_POST['topic_id'] ?? null;

    if ($topic_id !== null && $topic_id !== '') {
        $topic_id = (int) $topic_id;
        if (!$model->isValidTopic($topic_id)) {
            $topic_id = null;
        }
    } else {
        $topic_id = null;
    }

    if (empty($title) || empty($content)) {
        $error = 'Vui lòng điền đầy đủ tiêu đề và nội dung';
    } else {
        $user_id = $_SESSION['user_id'];
        $post_id = $model->addPost($user_id, $title, $content, $topic_id);
        if ($post_id) {
            header("Location: post.php?id=$post_id");
            exit();
        } else {
            $error = 'Đăng bài thất bại';
        }
    }
}

?>