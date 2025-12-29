<?php
require_once '../../includes/repo/auth.php';
requireLogin();
requireRole(['Admin', 'Manager', 'Super Admin']);

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
    header('Location: list.php');
    exit;
}

// Check task exists
$task = $taskService->getTaskById($taskId);
if (!$task) {
    $_SESSION['swal'] = [
        'icon'  => 'error',
        'title' => 'Not Found',
        'text'  => 'Task not found'
    ];
    header('Location: list.php');
    exit;
}

// Soft delete
$taskService->softDeleteTask($taskId);

// Success message
$_SESSION['swal'] = [
    'icon'  => 'success',
    'title' => 'Deleted',
    'text'  => 'Task moved to deleted list'
];

header('Location: list.php');
exit;
