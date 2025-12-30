<?php
require_once '../../includes/repo/auth.php';
requireLogin();
requireRole(['Super Admin', 'Admin', 'Manager']);

require_once '../../config/database.php';
require_once '../../includes/services/TaskService.php';

$db = (new Database())->getConnection();
$taskService = new TaskService($db);

$taskId = (int)($_GET['id'] ?? 0);

// Validate ID
if (!$taskId) {
    $_SESSION['swal'] = [
        'icon'  => 'error',
        'title' => 'Invalid Request',
        'text'  => 'Invalid task ID'
    ];
    header('Location: deleted.php');
    exit;
}

// Check task exists (even if deleted)
$task = $taskService->getTaskById($taskId);
if (!$task) {
    $_SESSION['swal'] = [
        'icon'  => 'error',
        'title' => 'Not Found',
        'text'  => 'Task not found'
    ];
    header('Location: deleted.php');
    exit;
}

// Restore task
$taskService->restoreTask($taskId);

// Success feedback
$_SESSION['swal'] = [
    'icon'  => 'success',
    'title' => 'Restored',
    'text'  => 'Task restored successfully'
];

header('Location: deleted.php');
exit;
