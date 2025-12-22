<?php
require_once '../../includes/auth.php';
requireLogin();
requireRole(['Super Admin', 'Admin']);
require_once '../../config/database.php';
require_once '../../includes/UserService.php';
require_once '../../includes/RoleService.php';



//database connection
$dbObj = new Database();
$db = $dbObj->getConnection();

//Services
$userService = new UserService($db);
$roleService = new RoleService($db);

//roles for dropdown
$roles = $roleService->getAllRoles(false);

$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role_id = $_POST['role_id'] ?? null;
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $countrycode = $_POST['country_code'];
    $phonenumber = $_POST['phone_number'];
    $address = $_POST['address'];
    $status = $_POST['status'];

    $result = $userService->createUser(
        $role_id,
        $firstname,
        $lastname,
        $email,
        $password,
        $countrycode,
        $phonenumber,
        $address,
        $status
    );
    if ($result['success']) {
        $successMessage = $result['message'];
    } else {
        $errors = $result['errors'];
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Add User</title>
    <link rel="stylesheet" href="../../assets/css/form.css">
    <?php require_once '../../includes/header.php'; ?>
</head>

<body>
    <div class="form-container">
        <div class="form-card">
            <h2>Add New User</h2>
            <a href="list.php">Back to Users List</a>
            <br><br>

            <?php if (!empty($errors)): ?>
                <div class="alert error">
                    <ul>
                        <?php foreach ($errors as $error) { ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php } ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($successMessage): ?>
                <div class="alert success">
                    <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <div class="form-group">
                    <label>Role:</label><br>
                    <select name="role_id" required>
                        <option value> --seletc role-- </option>
                        <?php foreach ($roles as $role) { ?>
                            <option value="<?php echo $role['id']; ?>">
                                <?php echo htmlspecialchars($role['name']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>First Name:</label><br>
                    <input type="text" name="firstname" required>
                </div>

                <div class="form-group">
                    <label>Last Name:</label><br>
                    <input type="text" name="lastname" required>
                </div>

                <div class="form-group">
                    <label>Email:</label><br>
                    <input type="email" name="email" required>
                </div>

                <div class="form-group">
                    <label>Password:</label><br>
                    <input type="password" name="password" required>
                    <small> Must contain at least 8 characters, including uppercase, lowercase, number, and special character. </small>
                </div>

                <div class="form-group">
                    <label>Country Code:</label><br>
                    <input type="text" name="country_code" placeholder="+91">
                </div>

                <div class="form-group">
                    <label>Phone Number:</label><br>
                    <input type="text" name="phone_number" placeholder="1234567890">
                </div>

                <div class="form-group">
                    <label>Address:</label><br>
                    <textarea name="address"></textarea>
                </div>

                <div class="form-group">
                    <label>Status:</label><br>
                    <select name="status" required>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn">Add User</button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>