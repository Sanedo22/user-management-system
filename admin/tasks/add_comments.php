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
    header('Location:'. BASE_URL . '/admin/users/user_tasks.php');
    exit;
}

$taskService->addComment($taskId, $userId, $comment);

header('Location: '. BASE_URL . ' /admin/users/user_tasks.php');
exit;
