<?php
session_start();
date_default_timezone_set('Asia/Kolkata');
require_once '../config/constants.php'
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>

    <!-- SB Admin 2 CSS -->
    <link href="<?= BASE_URL ?>assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body class="bg-gradient-primary">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-xl-4 col-lg-5 col-md-6">

            <div class="card shadow-lg my-5">
                <div class="card-body p-4">

                    <div class="text-center mb-4">
                        <h1 class="h4 text-gray-900">Forgot Password</h1>
                        <p class="small text-muted">
                            Enter your email to receive a reset link
                        </p>
                    </div>

                    <?php if (isset($_GET['status']) && $_GET['status'] === 'not_found'): ?>
                        <div class="alert alert-danger">
                            Email does not exist
                        </div>
                    <?php endif; ?>

                    <form method="post" action="forget_password_process.php">
                        <div class="form-group">
                            <input type="email"
                                   name="email"
                                   class="form-control form-control-user"
                                   placeholder="Email"
                                   required>
                        </div>

                        <button type="submit"
                                class="btn btn-primary btn-user btn-block">
                            Send Reset Link
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <a class="small" href="login.php">Back to Login</a>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<!-- Scripts -->
<script src="<?= BASE_URL ?>assets/vendor/jquery/jquery.min.js"></script>
<script src="<?= BASE_URL ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/sb-admin-2.min.js"></script>

</body>
</html>
