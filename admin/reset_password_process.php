<?php
require_once '../config/database.php';
require_once '../includes/UserService.php';

if (
    !isset($_POST['token']) ||
    !isset($_POST['password']) ||
    !isset($_POST['confirm_password'])
) {
    die('Invalid request');
}

$token = $_POST['token'];
$password = $_POST['password'];
$confirmPassword = $_POST['confirm_password'];

if ($password !== $confirmPassword) {
    die('PAsswords do not match');
}

$db = (new Database())->getConnection();
$userService = new UserService($db);

$resetData = $userService->getPasswordResetByToken($token);

if (!$resetData) {
    die('Reset link is expired!');
}

//update password
$userService->updateUserPassword(
    $resetData['user_id'],
    password_hash($password, PASSWORD_DEFAULT)
);

$userService->markPasswordResetUsed($resetData['id']);
header("Location: login.php?reset=success");
exit;
