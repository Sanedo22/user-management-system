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

    // ⏱ 15 minutes timeout
    $timeout = 300;

    if (
        isset($_SESSION['last_activity']) &&
        (time() - $_SESSION['last_activity']) > $timeout
    ) {
        session_unset();
        session_destroy();
        header('Location: ../../admin/login.php?timeout=1');
        exit();
    }

    // update activity time
    $_SESSION['last_activity'] = time();
}

function requireRole($roles = [])
{
    requireLogin();

    if (!in_array($_SESSION['user']['role_name'], $roles)) {
        header('Location: ../admin/users/dashboard.php');
        exit();
    }
}
