<?php
require_once '../../includes/repo/auth.php';
requireLogin();
requireRole(['Super Admin', 'Admin']);

require_once '../../config/database.php';
require_once '../../includes/services/UserService.php';
require_once '../../includes/services/RoleService.php';

// db connection
$dbObj = new Database();
$db = $dbObj->getConnection();

// services
$userService = new UserService($db);
$roleService = new RoleService($db);

// validate user id
if (!isset($_GET['id'])) {
    header('Location: list.php');
    exit();
}

$userId = $_GET['id'];

// fetch user
$user = $userService->getUser($userId);
if (!$user) {
    header('Location: list.php');
    exit();
}

// fetch roles
$roles = $roleService->getAllRoles(false);

// RBAC: Admin cannot assign Super Admin
if ($_SESSION['user']['role_name'] === 'Admin') {
    $roles = array_filter($roles, function ($role) {
        return $role['name'] !== 'Super Admin';
    });
}

// field errors
$fieldErrors = [];

// preserve values
$role_id     = $user['role_id'];
$firstname   = $user['first_name'];
$lastname    = $user['last_name'];
$email       = $user['email'];
$countrycode = $user['country_code'];
$phonenumber = $user['phone_number'];
$address     = $user['address'];
$status      = $user['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $role_id     = $_POST['role_id'] ?? '';
    $firstname   = $_POST['firstname'] ?? '';
    $lastname    = $_POST['lastname'] ?? '';
    $email       = $_POST['email'] ?? '';
    $countrycode = $_POST['country_code'] ?? '';
    $phonenumber = $_POST['phone_number'] ?? '';
    $address     = $_POST['address'] ?? '';
    $status      = $_POST['status'] ?? '';

    /* -----------------------------
       SERVER-SIDE VALIDATIONS
    ------------------------------*/

    if ($role_id === '') {
        $fieldErrors['role_id'] = 'Please select a role';
    }

    // RBAC: Admin cannot assign Super Admin
    if ($_SESSION['user']['role_name'] === 'Admin') {
        $allRoles = $roleService->getAllRoles(false);
        foreach ($allRoles as $role) {
            if ($role['name'] === 'Super Admin' && $role_id == $role['id']) {
                $fieldErrors['role_id'] = 'You are not allowed to assign Super Admin role';
            }
        }
    }

    // RBAC: Super Admin cannot demote himself
    if (
        $_SESSION['user']['role_name'] === 'Super Admin' &&
        $_SESSION['user']['id'] == $userId &&
        $role_id != $user['role_id']
    ) {
        $fieldErrors['role_id'] = 'You cannot change your own Super Admin role';
    }

    /* -----------------------------
       UPDATE USER IF VALID
    ------------------------------*/
    if (empty($fieldErrors)) {

        $role_id = $role_id ?: null;

        $result = $userService->updateUser(
            $userId,
            $role_id,
            $firstname,
            $lastname,
            $email,
            $countrycode,
            $phonenumber,
            $address,
            $status
        );

        if ($result['success']) {

            $_SESSION['swal'] = [
                'icon'  => 'success',
                'title' => 'User Updated',
                'text'  => $result['message']
            ];

            header('Location: list.php');
            exit();

        } else {
            foreach ($result['errors'] as $error) {
                if (stripos($error, 'email') !== false) {
                    $fieldErrors['email'] = $error;
                } elseif (stripos($error, 'first') !== false) {
                    $fieldErrors['firstname'] = $error;
                } elseif (stripos($error, 'last') !== false) {
                    $fieldErrors['lastname'] = $error;
                } elseif (stripos($error, 'phone') !== false) {
                    $fieldErrors['phone_number'] = $error;
                } else {
                    $fieldErrors['general'][] = $error;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit User</title>
    <link rel="stylesheet" href="../../assets/css/form.css">
    <?php require_once '../../includes/header.php'; ?>
    <style>
        .field-error {
            color: red;
            font-size: 13px;
            margin-top: 4px;
        }
    </style>
</head>

<body>

<div class="form-container">
    <div class="form-card">
        <h2>Edit User</h2>
        <a href="list.php">Back to Users List</a>
        <br><br>

        <?php if (!empty($fieldErrors['general'])): ?>
            <div class="field-error">
                <?php foreach ($fieldErrors['general'] as $err): ?>
                    <?= htmlspecialchars($err) ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post">

            <div class="form-group">
                <label>Role:</label><br>
                <select name="role_id">
                    <option value="">-- select role --</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= $role['id'] ?>" <?= ($role_id == $role['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($role['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($fieldErrors['role_id'])): ?>
                    <div class="field-error"><?= htmlspecialchars($fieldErrors['role_id']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>First Name:</label><br>
                <input type="text" name="firstname" value="<?= htmlspecialchars($firstname) ?>">
                <?php if (isset($fieldErrors['firstname'])): ?>
                    <div class="field-error"><?= htmlspecialchars($fieldErrors['firstname']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Last Name:</label><br>
                <input type="text" name="lastname" value="<?= htmlspecialchars($lastname) ?>">
                <?php if (isset($fieldErrors['lastname'])): ?>
                    <div class="field-error"><?= htmlspecialchars($fieldErrors['lastname']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Email:</label><br>
                <input type="email" name="email" value="<?= htmlspecialchars($email) ?>">
                <?php if (isset($fieldErrors['email'])): ?>
                    <div class="field-error"><?= htmlspecialchars($fieldErrors['email']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Country Code:</label><br>
                <select name="country_code">
                    <option value="">-- select country code --</option>
                    <option value="+91" <?= ($countrycode === '+91') ? 'selected' : '' ?>>India (+91)</option>
                    <option value="+1" <?= ($countrycode === '+1') ? 'selected' : '' ?>>USA / Canada (+1)</option>
                    <option value="+44" <?= ($countrycode === '+44') ? 'selected' : '' ?>>UK (+44)</option>
                    <option value="+61" <?= ($countrycode === '+61') ? 'selected' : '' ?>>Australia (+61)</option>
                    <option value="+81" <?= ($countrycode === '+81') ? 'selected' : '' ?>>Japan (+81)</option>
                    <option value="+971" <?= ($countrycode === '+971') ? 'selected' : '' ?>>UAE (+971)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Phone Number:</label><br>
                <input type="text" name="phone_number" value="<?= htmlspecialchars($phonenumber) ?>">
                <?php if (isset($fieldErrors['phone_number'])): ?>
                    <div class="field-error"><?= htmlspecialchars($fieldErrors['phone_number']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Address:</label><br>
                <textarea name="address"><?= htmlspecialchars($address) ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn">Update User</button>
            </div>

        </form>
    </div>
</div>

</body>
</html>
