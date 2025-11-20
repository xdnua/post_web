<?php
if (session_status() === PHP_SESSION_NONE)
    session_start();
require_once __DIR__ . '/../models/ReadHistoryModel.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$model = new ReadHistoryModel($conn);
$history = $model->getUserHistory($user_id);

?>