<?php
require_once '../../includes/repo/auth.php';
requireLogin();

require_once '../../config/database.php';
require_once '../../includes/services/TaskService.php';

$redirect = $_POST['redirect'] ?? $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirect);
    exit;
}

$taskId = $_POST['task_id'] ?? null;
$status = $_POST['status'] ?? null;

$allowedStatuses = ['Pending', 'In Progress', 'Completed'];

if (!$taskId || !in_array($status, $allowedStatuses)) {
    header('Location: ' . $redirect);
    exit;
}

$userId = $_SESSION['user']['id'];

$db = (new Database())->getConnection();
$taskService = new TaskService($db);

$task = $taskService->getTaskById($taskId);

if (!$task || (int)$task['assigned_to'] !== (int)$userId) {
    header('Location: ' . $redirect);
    exit;
}

$taskService->updateTaskStatus($taskId, $status, $userId);

header('Location: ' . $redirect);
exit;
