<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function requireLogin()
{
    if (!isset($_SESSION['user'])) {
        header('Location: ../../admin/login.php');
        exit();
    }
}

function requireRole($roles = [])
{
    requireLogin();

    if (!in_array($_SESSION['user']['role_name'], $roles)) {
        header('Location: ../admin/users/dashboard.php');
        exit();
    }
}
