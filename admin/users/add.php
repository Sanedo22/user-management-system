<?php
require_once '../../includes/auth.php';
requireLogin();
requireRole(['Super Admin', 'Admin']);

require_once '../../config/database.php';
require_once '../../includes/UserService.php';
require_once '../../includes/RoleService.php';

// database connection
$dbObj = new Database();
$db = $dbObj->getConnection();

// services
$userService = new UserService($db);
$roleService = new RoleService($db);

// fetch roles
$roles = $roleService->getAllRoles(false);

// RBAC: Admin cannot assign Super Admin
if ($_SESSION['user']['role_name'] === 'Admin') {
    $roles = array_filter($roles, function ($role) {
        return $role['name'] !== 'Super Admin';
    });
}

// field-wise errors
$fieldErrors = [];

// preserve values
$role_id = $firstname = $lastname = $email = $password = '';
$countrycode = $phonenumber = $address = $status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $role_id      = $_POST['role_id'] ?? '';
    $firstname    = $_POST['firstname'] ?? '';
    $lastname     = $_POST['lastname'] ?? '';
    $email        = $_POST['email'] ?? '';
    $password     = $_POST['password'] ?? '';
    $countrycode  = $_POST['country_code'] ?? '';
    $phonenumber  = $_POST['phone_number'] ?? '';
    $address      = $_POST['address'] ?? '';
    $status       = $_POST['status'] ?? '';


    if ($role_id === '') {
        $fieldErrors['role_id'] = 'Please select a role';
    }

    // RBAC protection (server-side)
    if ($_SESSION['user']['role_name'] === 'Admin') {
        $allRoles = $roleService->getAllRoles(false);
        foreach ($allRoles as $role) {
            if ($role['name'] === 'Super Admin' && $role_id == $role['id']) {
                $fieldErrors['role_id'] = 'You are not allowed to assign Super Admin role';
            }
        }
    }

    if (empty($fieldErrors)) {

        // normalize role_id for DB
        $role_id = $role_id ?: null;

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

            $_SESSION['swal'] = [
                'icon'  => 'success',
                'title' => 'User Created',
                'text'  => $result['message']
            ];

            header('Location: list.php');
            exit();

        } else {
            // map service errors to fields
            foreach ($result['errors'] as $error) {
                if (stripos($error, 'email') !== false) {
                    $fieldErrors['email'] = $error;
                } elseif (stripos($error, 'password') !== false) {
                    $fieldErrors['password'] = $error;
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
    <title>Add User</title>
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
        <h2>Add New User</h2>
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
                <label>Password:</label><br>
                <input type="password" name="password">
                <?php if (isset($fieldErrors['password'])): ?>
                    <div class="field-error"><?= htmlspecialchars($fieldErrors['password']) ?></div>
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

            <div class="form-group">
                <label>Status:</label><br>
                <select name="status">
                    <option value="">-- select status --</option>
                    <option value="1" <?= ($status === '1') ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= ($status === '0') ? 'selected' : '' ?>>Inactive</option>
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
