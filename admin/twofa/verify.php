<?php
session_start();

if (!isset($_SESSION['pending_2fa_user'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../../config/database.php';
require_once '../../includes/TotpService.php';

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
                'twofa_enabled' => $user['twofa_enabled']
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

<h2>Two-Factor Authentication</h2>

<form method="post">
    <input type="text" name="otp" placeholder="6-digit OTP" required>
    <br><br>
    <button type="submit">Verify</button>
</form>

<?php if ($error): ?>
    <p style="color:red"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>