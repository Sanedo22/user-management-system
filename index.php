<?php
session_start();

require_once 'config/database.php';
require_once 'config/constants.php';

// Check if user is already logged in
if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
    // User is logged in, redirect to appropriate dashboard
    $role = $_SESSION['user']['role_name'] ?? 'User';
    
    if (in_array($role, ['Super Admin', 'Admin'])) {
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
    } elseif ($role === 'Manager') {
        header('Location: ' . BASE_URL . '/admin/tasks/list.php');
    } else {
        header('Location: ' . BASE_URL . '/admin/users/dashboard.php');
    }
    exit();
}

// User is not logged in, redirect to login page
header('Location: ' . BASE_URL . '/admin/login.php');
exit();
?>
