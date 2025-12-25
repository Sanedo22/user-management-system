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

    if (!ctype_digit($otp) || strlen($otp) !== 6) {
        $_SESSION['swal'] = [
            'icon'  => 'error',
            'title' => 'Invalid OTP Format',
            'text'  => 'OTP must be a 6-digit number'
        ];
        header('Location: setup.php');
        exit();
    }

    if ($totp->verifyCode($secret, $otp)) {

        $sql = "UPDATE users SET twofa_enabled = 1, twofa_secret = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$secret, $userId]);

        $_SESSION['user']['twofa_enabled'] = 1;
        unset($_SESSION['twofa_setup_secret']);

        $_SESSION['swal'] = [
            'icon'  => 'success',
            'title' => '2FA Enabled',
            'text'  => 'Two-Factor Authentication has been enabled successfully'
        ];

        header('Location: ../dashboard.php');
        exit();
    }

    $_SESSION['swal'] = [
        'icon'  => 'error',
        'title' => 'Invalid OTP',
        'text'  => 'The OTP you entered is incorrect'
    ];
    header('Location: setup.php');
    exit();
}

$title = 'Enable Two-Factor Authentication';
require_once '../../includes/header.php';
?>

<div class="container-fluid">

    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">

            <div class="card shadow-sm">
                <div class="card-body text-center">

                    <h4 class="mb-3 text-gray-800">
                        Enable Two-Factor Authentication
                    </h4>

                    <p class="text-muted">
                        Scan this QR code using Google Authenticator,
                        Microsoft Authenticator, or Authy.
                    </p>

                    <img src="<?= htmlspecialchars($qrUrl) ?>"
                         alt="QR Code"
                         class="img-fluid mb-4"
                         style="max-width:200px;">

                    <form method="post" class="col-md-6 mx-auto">

                        <div class="form-group">
                            <input type="text"
                                   name="otp"
                                   class="form-control text-center"
                                   placeholder="Enter 6-digit OTP"
                                   required>
                        </div>

                        <button type="submit"
                                class="btn btn-primary btn-block">
                            Verify & Enable 2FA
                        </button>

                    </form>

                </div>
            </div>

        </div>
    </div>

</div>

<?php require_once '../../includes/footer.php'; ?>
