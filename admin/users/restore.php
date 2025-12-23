<?php
require_once '../../includes/auth.php';
requireLogin();
requireRole(['Super Admin', 'Admin']);

require_once '../../config/database.php';
require_once '../../includes/UserService.php';

// db
$dbObj = new Database();
$db = $dbObj->getConnection();

// service
$userService = new UserService($db);

// validate id
if (!isset($_GET['id'])) {
    header('Location: list.php');
    exit();
}

$restoreUserId = $_GET['id'];

// fetch user
$userToRestore = $userService->getAllUsers($restoreUserId);

if (!$userToRestore) {
    $_SESSION['swal'] = [
        'icon'  => 'error',
        'title' => 'User Not Found',
        'text'  => 'The selected user does not exist'
    ];
    header('Location: list.php');
    exit();
}

//RBAC rules
// cannot restore self
if ($_SESSION['user']['id'] == $restoreUserId) {
    $_SESSION['swal'] = [
        'icon'  => 'error',
        'title' => 'Action Not Allowed',
        'text'  => 'You cannot restore your own account'
    ];
    header('Location: list.php');
    exit();
}

// Admin cannot restore Super Admin
if (
    $_SESSION['user']['role_name'] === 'Admin' &&
    $userToRestore['role_name'] === 'Super Admin'
) {
    $_SESSION['swal'] = [
        'icon'  => 'error',
        'title' => 'Permission Denied',
        'text'  => 'You are not allowed to restore a Super Admin'
    ];
    header('Location: list.php');
    exit();
}

//restore user
$result = $userService->restoreUser($restoreUserId);

if ($result['success']) {
    $_SESSION['swal'] = [
        'icon'  => 'success',
        'title' => 'User Restored',
        'text'  => $result['message']
    ];
} else {
    $_SESSION['swal'] = [
        'icon'  => 'error',
        'title' => 'Restore Failed',
        'text'  => implode("\n", $result['errors'])
    ];
}

header('Location: list.php');
exit();
