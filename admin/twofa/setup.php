<?php
require_once '../../includes/auth.php';
requireLogin();

require_once '../../config/database.php';
require_once '../../includes/TotpService.php';

$db = (new Database())->getConnection();
$totp = new TotpService();

$userId = $_SESSION['user']['id'];
$email  = $_SESSION['user']['email'];

// check if already enabled
$sql = "SELECT twofa_enabled FROM users WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && $user['twofa_enabled']) {
    header('Location: ../dashboard.php');
    exit();
}

// generate secret only once per setup
if (!isset($_SESSION['twofa_setup_secret'])) {
    $_SESSION['twofa_setup_secret'] = $totp->generateSecret();
}

$secret = $_SESSION['twofa_setup_secret'];
$qrUrl  = $totp->getQrCodeUrl($email, $secret);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');
    $otp = str_replace(' ', '', $otp);

    if (!ctype_digit($otp) || strlen($otp) !== 6) {
        $error = 'Invalid OTP format.';
    } else if ($totp->verifyCode($secret, $otp)) {

        // enable 2FA in DB
        $sql = "UPDATE users SET twofa_enabled = 1, twofa_secret = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$secret, $userId]);

        // sync session
        $_SESSION['user']['twofa_enabled'] = 1;

        // cleanup setup secret
        unset($_SESSION['twofa_setup_secret']);

        header('Location: ../dashboard.php?2fa=enabled');
        exit();

    } else {
        $error = 'Invalid OTP.';
    }
}
?>
<link rel="stylesheet" href="../../assets/css/form.css">
<div class="form-container">
    <div class="form-card">
<h2>Enable Two-Factor Authentication</h2>

<p>Scan this QR code using Authy / Microsoft / Google Authenticator</p>

<img src="<?= htmlspecialchars($qrUrl) ?>" alt="QR Code">

<form method="post">
    <div class="form-group">
    <input type="text" name="otp" placeholder="6-digit OTP" required>
    <br><br>
    <div class="btn primary"><button class="form-actions" type="submit">Verify & Enable</button></div>
    </div>
</form>
    </div>
</div>

<?php if ($error): ?>
    <p style="color:red"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>
