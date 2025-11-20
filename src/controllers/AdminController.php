<?php
require_once __DIR__ . '/../models/AdminModel.php';
requireAdmin(); // Yêu cầu quyền admin

$model = new AdminModel($conn);

$total_posts = $model->getTotalPosts();
$total_users = $model->getTotalUsers();
$total_topics = $model->getTotalTopics();
?>