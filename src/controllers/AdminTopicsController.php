<?php
require_once __DIR__ . '/../models/AdminTopicsModel.php';
requireAdmin();

$model = new AdminTopicModel($conn);

$error = '';
$success = '';
$edit_topic = null;

// Thêm chủ đề
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_topic'])) {
    if ($model->addTopic($_POST['topic_name'])) {
        $success = 'Thêm chủ đề thành công!';
    } else {
        $error = 'Lỗi: Không thể thêm chủ đề.';
    }
    header('Location: topics.php');
    exit();
}

// Xóa chủ đề
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_topic_id'])) {
    if ($model->deleteTopic($_POST['delete_topic_id'])) {
        $success = 'Xóa chủ đề thành công!';
    } else {
        $error = 'Lỗi: Không thể xóa chủ đề.';
    }
    header('Location: topics.php');
    exit();
}

// Lấy dữ liệu để sửa
if (isset($_GET['edit'])) {
    $edit_topic = $model->getTopicById($_GET['edit']);
    if (!$edit_topic)
        $error = 'Chủ đề không tồn tại.';
}

// Cập nhật chủ đề
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_topic'])) {
    if ($model->updateTopic($_POST['topic_id'], $_POST['new_topic_name'])) {
        $success = 'Cập nhật chủ đề thành công!';
    } else {
        $error = 'Lỗi: Không thể cập nhật chủ đề.';
    }
    header('Location: topics.php');
    exit();
}

// Lấy danh sách chủ đề để hiển thị
$topics_result = $model->getAllTopics();

?>