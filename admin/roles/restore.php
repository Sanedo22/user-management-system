<?php
// auth & RBAC
require_once __DIR__ . '/../../includes/repo/auth.php';
requireLogin();
requireRole(['Super Admin']);

// dependencies
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/services/RoleService.php';

// validate id
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: list.php');
    exit;
}

$id = (int) $_GET['id'];

// db connection
$db = (new Database())->getConnection();

// service
$roleService = new RoleService($db);

// restore (ALL validation happens inside service)
$result = $roleService->restoreRole($id);

// swal response
if ($result['success']) {
    $_SESSION['swal'] = [
        'icon'  => 'success',
        'title' => 'Role Restored',
        'text'  => $result['message']
    ];
} else {
    $_SESSION['swal'] = [
        'icon'  => 'error',
        'title' => 'Restore Failed',
        'text'  => $result['errors'][0]
    ];
}

// redirect back
header('Location: list.php');
exit;
