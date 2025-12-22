<?php
require_once '../includes/header.php';
date_default_timezone_set('Asia/Kolkata');

?>

<head>
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<div class="auth-container">
<h2> Forgot Password </h2>

<?php if (isset($_GET['status']) && $_GET['status'] === 'sent'): ?>
    <p style="color: green;">
        If the email exists, a reset link has been sent.
    </p>
<?php endif; ?>
<form method="post" action="forget_password_process.php">
    <label>Email</label><br>
    <input type="email" name="email" required><br><br>
    <button type="submit">Send reset link</button>
</form>
</div>

<?php
require_once '../includes/footer.php';
?>