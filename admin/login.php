<?php
session_start();
require_once '../config/database.php';
require_once '../includes/AuthService.php';

$dbObj = new Database();
$db = $dbObj->getConnection();

$auth = new AuthService($db);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $result = $auth->login($email, $password);

    if(!$result['success']){
        $errors = $result['errors'];
    } else if(isset($result['twofa_required'])){
        header('Location: ../../user-management-system/admin/twofa/verify.php');
        exit();
    } else {
        header('Location: dashboard.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Login</title>
</head>

<body>

    <h2>Admin Login</h2>

    <?php if (!empty($errors)) { ?>
        <ul style="color:red;">
            <?php foreach ($errors as $error) { ?>
                <li><?php echo $error; ?></li>
            <?php } ?>
        </ul>
    <?php } ?>

    <form method="post">
        <input type="email" name="email" placeholder="Email" required><br><br>
        <input type="password" name="password" placeholder="Password" required><a href="forget_password.php">Forgot Password?</a>
        <br><br>
        <button type="submit">Login</button>
        <?php if (isset($_GET['reset']) && $_GET['reset'] === 'success'): ?>
            <p style="color: green;">Password reset successful. Please login.</p>
        <?php endif; ?>
        <?php if (isset($_GET['timeout'])): ?>
            <p style="color:red;">Session expired. Please login again.</p>
        <?php endif; ?>


    </form>

</body>

</html>