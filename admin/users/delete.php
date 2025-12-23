<?php
require_once '../../includes/repo/auth.php';
requireLogin();
requireRole(['Super Admin', 'Admin']);

require_once '../../config/database.php';
require_once '../../includes/services/UserService.php';

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

$deleteUserId = $_GET['id'];

// fetch user to delete
$userToDelete = $userService->getUser($deleteUserId);

if (!$userToDelete) {
    $_SESSION['swal'] = [
        'icon'  => 'error',
        'title' => 'User Not Found',
        'text'  => 'The selected user does not exist'
    ];
    header('Location: list.php');
    exit();
}

//RBAC rules
// cannot delete self
if ($_SESSION['user']['id'] == $deleteUserId) {
    $_SESSION['swal'] = [
        'icon'  => 'error',
        'title' => 'Action Not Allowed',
        'text'  => 'You cannot delete your own account'
    ];
    header('Location: list.php');
    exit();
}

// Admin cannot delete Super Admin
if (
    $_SESSION['user']['role_name'] === 'Admin' &&
    $userToDelete['role_name'] === 'Super Admin'
) {
    $_SESSION['swal'] = [
        'icon'  => 'error',
        'title' => 'Permission Denied',
        'text'  => 'You are not allowed to delete a Super Admin'
    ];
    header('Location: list.php');
    exit();
}

$result = $userService->deleteUser($deleteUserId);

if ($result['success']) {
    $_SESSION['swal'] = [
        'icon'  => 'success',
        'title' => 'User Deleted',
        'text'  => $result['message']
    ];
} else {
    $_SESSION['swal'] = [
        'icon'  => 'error',
        'title' => 'Delete Failed',
        'text'  => implode("\n", $result['errors'])
    ];
}

header('Location: list.php');
exit();
