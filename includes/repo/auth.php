<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';

function requireLogin()
{
    if (!isset($_SESSION['user'])) {

        $_SESSION['swal'] = [
            'icon' => 'warning',
            'title' => 'Login Required',
            'text' => 'Please login to continue'
        ];
        header('Location: ../../admin/login.php');
        exit();
    }

    $db = (new Database())->getConnection();

    // Validate session from DB
    $sql = "SELECT * FROM user_sessions WHERE session_id = ? AND is_active = 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([session_id()]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        session_unset();
        session_destroy();

        $_SESSION['swal'] = [
            'icon'  => 'warning',
            'title' => 'Session Expired',
            'text'  => 'Your session has expired. Please login again.'
        ];
        header('Location: ../../admin/login.php');
        exit();
    }

    //verify user still exists & not deleted
    $sql = "SELECT deleted_at FROM users WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$_SESSION['user']['id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['deleted_at'] !== null) {

        $_SESSION['swal'] = [
            'icon'  => 'error',
            'title' => 'Account Disabled',
            'text'  => 'Your account has been removed by an administrator.'
        ];

        // deactivate session record
        $sql = "UPDATE user_sessions SET is_active = 0 WHERE session_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([session_id()]);

        session_unset();
        session_destroy();

        header('Location: ../../admin/login.php');
        exit();
    }

    // update last activity
    $sql = "UPDATE user_sessions SET last_activity = NOW() WHERE session_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([session_id()]);

    $_SESSION['last_activity'] = time();
}

function requireRole($roles = [])
{
    requireLogin();

    if (!in_array($_SESSION['user']['role_name'], $roles)) {
        $_SESSION['swal'] = [
            'icon'  => 'error',
            'title' => 'Access Denied',
            'text'  => 'You do not have permission to access this page'
        ];
        if ($_SESSION['user']['role_name'] === 'User') {
            header('Location: ../../admin/users/dashboard.php');
        } else {
            header('Location: ../../admin/dashboard.php');
        }
        exit();
    }
}
