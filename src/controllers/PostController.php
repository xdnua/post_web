<?php
require_once __DIR__ . '/../models/PostModel.php';

$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$search_term = trim($_GET['search'] ?? '');
$topic_id = isset($_GET['topic_id']) ? (int) $_GET['topic_id'] : null;

$model = new PostModel($conn);

$total_posts = $model->countPosts($search_term, $topic_id);
$total_pages = $model->getTotalPages($total_posts);
$result = $model->getPosts($page, $search_term, $topic_id);

?>