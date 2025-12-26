<?php
session_start();

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/services/UserService.php';

if (!isset($_GET['token'])) {
    die('Invalid reset link');
}

$token = $_GET['token'];

$db = (new Database())->getConnection();
$userService = new UserService($db);

// validate token
$resetData = $userService->getPasswordResetByToken($token);

if (!$resetData) {
    die('Reset link is invalid or expired');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>

    <!-- SB Admin 2 CSS -->
    <link href="<?= BASE_URL ?>/assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body class="bg-gradient-primary">

<div class="container">

    <div class="row justify-content-center">
        <div class="col-xl-4 col-lg-5 col-md-6">

            <div class="card shadow-lg my-5">
                <div class="card-body p-4">

                    <div class="text-center mb-4">
                        <h1 class="h4 text-gray-900">Reset Password</h1>
                        <p class="small text-muted">
                            Enter your new password below.
                        </p>
                    </div>

                    <form method="post" action="reset_password_process.php">
                        <input type="hidden"
                               name="token"
                               value="<?= htmlspecialchars($token) ?>">

                        <div class="form-group">
                            <input type="password"
                                   name="password"
                                   class="form-control form-control-user"
                                   placeholder="New Password"
                                   required>
                        </div>

                        <div class="form-group">
                            <input type="password"
                                   name="confirm_password"
                                   class="form-control form-control-user"
                                   placeholder="Confirm Password"
                                   required>
                        </div>

                        <button type="submit"
                                class="btn btn-primary btn-user btn-block">
                            Reset Password
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <a class="small" href="login.php">
                            Back to Login
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </div>

</div>

<!-- Scripts -->
<script src="<?= BASE_URL ?>/assets/vendor/jquery/jquery.min.js"></script>
<script src="<?= BASE_URL ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/sb-admin-2.min.js"></script>

</body>
</html>
