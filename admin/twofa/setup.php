<?php
require_once '../../includes/repo/auth.php';
requireLogin();

require_once '../../config/database.php';
require_once '../../includes/services/TotpService.php';

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
    $_SESSION['swal'] = [
        'icon'  => 'info',
        'title' => 'Already Enabled',
        'text'  => 'Two-Factor Authentication is already enabled'
    ];
    header('Location: ../dashboard.php');
    exit();
}

// generate secret only once per setup
if (!isset($_SESSION['twofa_setup_secret'])) {
    $_SESSION['twofa_setup_secret'] = $totp->generateSecret();
}

$secret = $_SESSION['twofa_setup_secret'];
$qrUrl  = $totp->getQrCodeUrl($email, $secret);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');
    $otp = str_replace(' ', '', $otp);

    //OTP format invalid
    if (!ctype_digit($otp) || strlen($otp) !== 6) {
        $_SESSION['swal'] = [
            'icon'  => 'error',
            'title' => 'Invalid OTP Format',
            'text'  => 'OTP must be a 6-digit number'
        ];
        header('Location: setup.php');
        exit();
    }

    //OTP correct
    if ($totp->verifyCode($secret, $otp)) {

        // enable 2FA in DB
        $sql = "UPDATE users SET twofa_enabled = 1, twofa_secret = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$secret, $userId]);

        // sync session
        $_SESSION['user']['twofa_enabled'] = 1;

        // cleanup setup secret
        unset($_SESSION['twofa_setup_secret']);

        $_SESSION['swal'] = [
            'icon'  => 'success',
            'title' => '2FA Enabled',
            'text'  => 'Two-Factor Authentication has been enabled successfully'
        ];

        header('Location: ../dashboard.php');
        exit();
    }

    // ❌ OTP incorrect
    $_SESSION['swal'] = [
        'icon'  => 'error',
        'title' => 'Invalid OTP',
        'text'  => 'The OTP you entered is incorrect'
    ];
    header('Location: setup.php');
    exit();
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
                <div class="btn primary">
                    <button class="form-actions" type="submit">Verify & Enable</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php require_once '../../includes/services/swal_render.php'; ?>
