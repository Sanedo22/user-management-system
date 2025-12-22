<?php
require_once '../config/database.php';
require_once '../includes/UserService.php';

if (!isset($_GET['token'])) {
    die('Invalid reset link');
}

$token = $_GET['token'];

$db = (new Database())->getConnection();
$userService = new UserService($db);

//validate token
$resetData = $userService->getPasswordResetByToken($token);

if (!$resetData) {
    die('reset link is invalid or expired');
}
?>

<head>
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<div class="auth-container">
<h2> Reset Password </h2>

<form method="post" action="reset_password_process.php">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

    <label>New Password</label><br>
    <input type="password" name="password" required><br><br>

    <label>Confirm Password</label><br>
    <input type="password" name="confirm_password" required><br><br>

    <button type="submit">Reset Password</button>

    <div class="auth-links">
        <a href="login.php">Back to login</a>
    </div>
</form>
</div>