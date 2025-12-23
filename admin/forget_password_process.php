<?php
require_once '../config/database.php';
require_once '../includes/services/UserService.php';

date_default_timezone_set('Asia/Kolkata');

if (!isset($_POST['email'])) {
    header('Location: forget_password.php');
    exit();
}

$email = trim($_POST['email']);

$db = (new Database())->getConnection();
$userService = new UserService($db);

$user = $userService->getUserByEmail($email);

if (!$user) {
    header('Location: forget_password.php?status=sent');
    exit();
}

//generating token
$token = bin2hex(random_bytes(32));
$expireAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));

//token store
$userService->createPasswordResetToken($user['id'], $token, $expireAt);

//reset link configure
$resetLink = "https://localhost/user_management-system/user-management-system/admin/reset_password.php?token=" . $token;

echo "<p>Reset link (for testing only):</p>";
echo "<a href='$resetLink'>$resetLink</a>";
exit;


//email
$subject = "Password rest request";
$message = "Hello {$user['first_name']},
    click below link to reset password:
    $resetLink";

$headers = "From: localhost@example.com";

@mail($email, $subject, $message, $headers);

header('Location: forget_password.php?status=sent');
exit;
