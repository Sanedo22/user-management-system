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

    if ($result['success']) {
        header('Location: dashboard.php');
        exit;
    } else {
        $errors = $result['errors'];
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
    <input type="password" name="password" placeholder="Password" required><br><br>
    <button type="submit">Login</button>
</form>

</body>
</html>
