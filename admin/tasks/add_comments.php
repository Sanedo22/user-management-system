<?php
require_once '../../includes/repo/auth.php';
requireLogin();


require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../includes/services/TaskService.php';

$db = (new Database())->getConnection();
$taskService = new TaskService($db);

$taskId  = $_POST['task_id'] ?? null;
$comment = trim($_POST['comment'] ?? '');
$userId  = $_SESSION['user']['id'];

if (!$taskId || $comment === '') {
    $redirect = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/admin/users/user_tasks.php');
    header('Location: ' . $redirect);
    exit;
}

$taskService->addComment($taskId, $userId, $comment);

$redirect = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/admin/users/user_tasks.php');
header('Location: ' . $redirect);
exit;
