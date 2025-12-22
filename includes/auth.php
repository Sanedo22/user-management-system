<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function requireLogin()
{
    if (!isset($_SESSION['user'])) {
        header('Location: ../../admin/login.php');
        exit();
    }

    $db = (new Database())->getConnection();

    //Validateing session from DB
    $sql = "SELECT * FROM user_sessions WHERE session_id = ? AND is_active=1";
    $stmt = $db->prepare($sql);
    $stmt->execute([session_id()]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        session_unset();
        session_destroy();
        header('Location: ../../admin/login.php?session_expired=1');
        exit();
    }

    //update last activity
    $sql = "UPDATE user_sessions SET last_activity = NOW() WHERE session_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([session_id()]);

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
