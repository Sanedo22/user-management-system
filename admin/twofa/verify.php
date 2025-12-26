<?php
session_start();

if (!isset($_SESSION['pending_2fa_user'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../../config/database.php';
require_once '../../includes/services/TotpService.php';

$db = (new Database())->getConnection();
$totp = new TotpService();

$userId = $_SESSION['pending_2fa_user'];

//fetech user and role
$sql = "SELECT u.*, r.name AS role_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE u.id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || empty($user['twofa_secret'])) {
    session_unset();
    session_destroy();
    header('Location: ../login.php');
    exit();
}

$error = '';

//otp submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');
    $otp = str_replace(' ', '', $otp);

    if (!ctype_digit($otp) || strlen($otp) !== 6) {
        $error = 'Invalid otp format';
    } else {
        $secret = strtoupper($user['twofa_secret']);

        if ($totp->verifyCode($secret, $otp)) {

            session_regenerate_id(true);

            $_SESSION['user'] = [
                'id'        => $user['id'],
                'email'     => $user['email'],
                'role_id'   => $user['role_id'],
                'role_name' => $user['role_name'],
                'twofa_enabled' => 1
            ];

            $_SESSION['last_activity'] = time();

            unset($_SESSION['pending_2fa_user']);

            $sql = "INSERT INTO user_sessions
                (user_id, session_id, device_info, ip_address, last_activity)
                VALUES (?, ?, ?, ?, NOW())";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                $user['id'],
                session_id(),
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
            ]);

            if ($user['role_name'] === 'Super Admin' || $user['role_name'] === 'Admin') {
                header('Location: ../dashboard.php');
            } else {
                header('Location: ../users/dashboard.php');
            }
            exit();
        } else {
            $error = 'Invalid OTP';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Two-Factor Authentication</title>

    <!-- SB Admin 2 CSS -->
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">

</head>

<body class="bg-gradient-primary">

    <div class="container">

        <div class="row justify-content-center">

            <div class="col-xl-4 col-lg-5 col-md-6">

                <div class="card shadow-lg my-5">
                    <div class="card-body p-4">

                        <div class="text-center mb-4">
                            <h1 class="h4 text-gray-900">
                                Two-Factor Authentication
                            </h1>
                            <p class="text-muted small">
                                Enter the 6-digit code from your authenticator app
                            </p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger text-center">
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <form method="post">

                            <div class="form-group">
                                <input type="text"
                                    name="otp"
                                    class="form-control form-control-user text-center"
                                    placeholder="6-digit OTP"
                                    maxlength="6"
                                    required>
                            </div>

                            <button type="submit"
                                class="btn btn-primary btn-user btn-block">
                                Verify OTP
                            </button>

                        </form>

                    </div>
                </div>

            </div>

        </div>

    </div>

    <!-- Scripts -->
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sb-admin-2.min.js"></script>

</body>

</html>