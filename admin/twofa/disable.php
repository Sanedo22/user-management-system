<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit();
}

$adminRoles = ['Super Admin', 'Admin'];

$redirectAfterDisable2FA = in_array($_SESSION['user']['role_name'], $adminRoles)
    ? '../dashboard.php'
    : '../users/dashboard.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirectAfterDisable2FA);
    exit();
}

$password = $_POST['password'] ?? '';

$db = (new Database())->getConnection();
$userId = $_SESSION['user']['id'];

// fetch user password
$sql = "SELECT password FROM users WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password'])) {
    header('Location:' . $redirectAfterDisable2FA . '?error=wrong_password');
    exit();
}

// disable 2FA
$sql = "UPDATE users SET twofa_enabled = 0, twofa_secret = NULL WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$userId]);

// update session
$_SESSION['user']['twofa_enabled'] = 0;

$_SESSION['swal'] = [
    'icon'  => 'success',
    'title' => '2FA Disabled',
    'text'  => 'Two-Factor Authentication has been disabled successfully'
];

header('Location: ' . $redirectAfterDisable2FA . '?2fa=disabled');
exit();
