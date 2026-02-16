<?php
require_once '../config/constants.php';
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
    header('Location: forget_password.php?status=not_found');
    exit();
}


// make token
$token = bin2hex(random_bytes(32));
$expireAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));

// save token
$userService->createPasswordResetToken($user['id'], $token, $expireAt);

// create link
$resetLink = BASE_URL . "/admin/reset_password.php?token=" . $token;

// local test mode
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password Link</title>
    <link href="<?= BASE_URL ?>/assets/css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-primary">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-6 col-lg-7 col-md-9">
                <div class="card shadow-lg my-5">
                    <div class="card-body p-5">
                        <div class="text-center">
                            <h1 class="h4 text-gray-900 mb-4">Password Reset Link</h1>
                            <p class="mb-4">You are in Local Mode. Use the link below to reset your password:</p>
                            <div class="alert alert-success break-word">
                                <a href="<?= $resetLink ?>"><?= $resetLink ?></a>
                            </div>
                            <a href="login.php" class="btn btn-secondary">Back to Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php
exit;

