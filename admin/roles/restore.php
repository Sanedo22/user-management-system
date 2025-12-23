<?php
require_once '../../includes/repo/auth.php';
requireLogin();
requireRole(['Super Admin']);

require_once '../../config/database.php';
require_once '../../includes/Services/RoleService.php';

// validate id
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

// fetch role (including deleted)
$role = $roleService->getRole($id);

if (!$role) {
    $_SESSION['swal'] = [
        'icon'  => 'error',
        'title' => 'Not Found',
        'text'  => 'Role does not exist'
    ];
    header('Location: list.php');
    exit;
}

//RBAC rules
// Super Admin role must never be restored
if ($role['name'] === 'Super Admin') {
    $_SESSION['swal'] = [
        'icon'  => 'error',
        'title' => 'Action Not Allowed',
        'text'  => 'Super Admin role cannot be restored'
    ];
    header('Location: list.php');
    exit;
}


//restore role
$result = $roleService->restoreRole($id);

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

header('Location: list.php');
exit;
