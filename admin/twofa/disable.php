<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard.php');
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
    header('Location: ../dashboard.php?error=wrong_password');
    exit();
}

// disable 2FA
$sql = "UPDATE users SET twofa_enabled = 0, twofa_secret = NULL WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$userId]);

// update session
$_SESSION['user']['twofa_enabled'] = 0;

header('Location: ../dashboard.php?2fa=disabled');
exit();
