<?php
session_start();

require_once '../../config/database.php';
require_once '../../includes/roleService.php';

if (!isset($_GET['id'])) {
    header('Location: list.php');
    exit;
}

$id = $_GET['id'];

// db connection
$dbObj = new Database();
$db = $dbObj->getConnection();

// role service
$roleService = new RoleService($db);

$result = $roleService->restoreRole($id);

if ($result['success']) {
    $_SESSION['swal'] = [
        'icon' => 'success',
        'title' => 'Restored',
        'text' => $result['message']
    ];
} else {
    $_SESSION['swal'] = [
        'icon' => 'error',
        'title' => 'Error',
        'text' => $result['errors'][0]
    ];
}

header('Location: list.php');
exit;
