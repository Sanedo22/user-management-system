<?php
require_once '../../includes/repo/auth.php';
requireLogin();
requireRole(['Super Admin']);

require_once '../../config/database.php';
require_once '../../includes/services/RoleService.php';

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

// fetch role
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

/* ---------------------------------
   RBAC PROTECTIONS
----------------------------------*/

// 1️⃣ Super Admin role cannot be deleted
if ($role['name'] === 'Super Admin') {
    $_SESSION['swal'] = [
        'icon'  => 'error',
        'title' => 'Action Not Allowed',
        'text'  => 'Super Admin role cannot be deleted'
    ];
    header('Location: list.php');
    exit;
}

// 2️⃣ Role assigned to users cannot be deleted
if ($roleService->isRoleAssignedToUsers($id)) {
    $_SESSION['swal'] = [
        'icon'  => 'error',
        'title' => 'Action Blocked',
        'text'  => 'This role is assigned to users and cannot be deleted'
    ];
    header('Location: list.php');
    exit;
}

//delete role
$result = $roleService->deleteRole($id);

if ($result['success']) {
    $_SESSION['swal'] = [
        'icon'  => 'success',
        'title' => 'Role Deleted',
        'text'  => $result['message']
    ];
} else {
    $_SESSION['swal'] = [
        'icon'  => 'error',
        'title' => 'Delete Failed',
        'text'  => $result['errors'][0]
    ];
}

header('Location: list.php');
exit;
