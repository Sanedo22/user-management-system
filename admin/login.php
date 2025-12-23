<?php
session_start();

require_once '../config/database.php';
require_once '../includes/services/AuthService.php';
require_once '../includes/services/TotpService.php';

$db = (new Database())->getConnection();
$auth = new AuthService($db);
$totp = new TotpService();

//normal login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['otp'])) {

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $result = $auth->login($email, $password);

    if (!$result['success']) {
        $_SESSION['swal'] = [
            'icon'  => 'error',
            'title' => 'Login Failed',
            'text'  => implode("\n", $result['errors'])
        ];
        header('Location: login.php');
        exit();
    }

    // 2FA required → stay on same page
    if (isset($result['twofa_required'])) {
        $_SESSION['twofa_stage'] = true;
        header('Location: login.php');
        exit();
    }

//Role based rediert
    $role = $_SESSION['user']['role_name'];

    if (in_array($role, ['Super Admin', 'Admin'])) {
        header('Location: dashboard.php');
    } else {
        header('Location: ../admin/users/dashboard.php');
    }
    exit();
}

//OTP submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp'])) {

    if (!isset($_SESSION['pending_2fa_user'])) {
        header('Location: login.php');
        exit();
    }

    $otp = trim($_POST['otp']);
    $otp = str_replace(' ', '', $otp);

    if (!ctype_digit($otp) || strlen($otp) !== 6) {
        $_SESSION['swal'] = [
            'icon'  => 'error',
            'title' => 'Invalid OTP',
            'text'  => 'OTP must be a 6-digit number'
        ];
        header('Location: login.php');
        exit();
    }

    // fetch user
    $sql = "SELECT u.*, r.name AS role_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$_SESSION['pending_2fa_user']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !$totp->verifyCode($user['twofa_secret'], $otp)) {
        $_SESSION['swal'] = [
            'icon'  => 'error',
            'title' => 'Verification Failed',
            'text'  => 'Invalid OTP. Please try again.'
        ];
        header('Location: login.php');
        exit();
    }

    // login success after OTP
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id'            => $user['id'],
        'email'         => $user['email'],
        'role_id'       => $user['role_id'],
        'role_name'     => $user['role_name'],
        'twofa_enabled' => 1
    ];

    unset($_SESSION['pending_2fa_user'], $_SESSION['twofa_stage']);

    // store session
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

//After 2fa
    if (in_array($user['role_name'], ['Super Admin', 'Admin'])) {
        header('Location: dashboard.php');
    } else {
        header('Location: ../admin/users/dashboard.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>

<div class="auth-container">
    <h2>Admin Login</h2>

    <?php if (!isset($_SESSION['twofa_stage'])): ?>
        <!-- NORMAL LOGIN FORM -->
        <form method="post">
            <input type="email" name="email" placeholder="Email" required><br><br>
            <input type="password" name="password" placeholder="Password" required>
            <br><br> <a href="forget_password.php">Forgot Password?</a>
            <button type="submit">Login</button>
        </form>
    <?php else: ?>
        <!-- OTP FORM -->
        <form method="post">
            <input type="text" name="otp" placeholder="6-digit OTP" required>
            <br><br>
            <button type="submit">Verify OTP</button>
        </form>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php require_once '../includes/services/swal_render.php'; ?>

</body>
</html>
