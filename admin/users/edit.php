<?php
require_once '../../includes/auth.php';
requireLogin();
requireRole(['Super Admin', 'Admin']);

require_once '../../config/database.php';
require_once '../../includes/UserService.php';
require_once '../../includes/roleService.php';




if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit;
}

$db = (new Database())->getConnection();
$userService = new UserService($db);
$roleService = new RoleService($db);

$user = $userService->getUser($_GET['id']);
if (!$user) {
    header("Location: list.php");
    exit;
}

$loggedInUser = $_SESSION['user'];

//admin cannot edit super admin
if (
    $loggedInUser['role_name'] === 'Admin' &&
    $user['role_name'] === 'Super Admin'
) {
    header("Location: list.php");
    exit();
}

$roles = $roleService->getAllRoles(false);
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $result = $userService->updateUser(
        $_GET['id'],
        $_POST['role_id'] ?? null,
        $_POST['first_name'],
        $_POST['last_name'],
        $_POST['email'],
        $_POST['country_code'],
        $_POST['phone_number'],
        $_POST['address'],
        $_POST['status'],
        null,
        $_POST['password'] ?? null
    );
    if ($result['success']) {
        $successMessage = $result['message'];
        // refresh user data
        $user = $userService->getUser($user['id']);
    } else {
        $errors = $result['errors'];
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Edit User</title>
    <link rel="stylesheet" href="../../assets/css/form.css">
    <?php require_once '../../includes/header.php'; ?>
</head>

<body>
    <div class="form-container">
        <div class="form-card">
            <h2>Edit User</h2>
            <a href="list.php">← Back to Users List</a>
            <br><br>
            <?php if ($errors) {
                foreach ($errors as $e) echo "<p style='color:red>$e</p>'";
            } ?>
            <?php if ($success) echo "<p style='color:green'>$success</p>"; ?>

            <form method="post" action="">
                <div class="form-group">
                    <input name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required><br><br>
                    <input name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required><br><br>
                    <input name="email" value="<?= htmlspecialchars($user['email']) ?>" required><br><br>
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <?php if ($user['role_name'] === 'Super Admin'): ?>
                        <strong>Super Admin</strong>
                        <input type="hidden" name="role_id" value="<?= $user['role_id'] ?>">
                    <?php else: ?>
                        <select name="role_id">
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id'] ?>" <?= $user['role_id'] == $r['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($r['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                    <br><br>
                </div>

                <div class="form-group">
                    <input name="country_code" value="<?= htmlspecialchars($user['country_code']) ?>"><br><br>
                    <input name="phone_number" value="<?= htmlspecialchars($user['phone_number']) ?>"><br><br>

                    <textarea name="address"><?= htmlspecialchars($user['address']) ?></textarea><br><br>

                    <select name="status">
                        <option value="1" <?= $user['status'] == 1 ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= $user['status'] == 0 ? 'selected' : '' ?>>Inactive</option>
                    </select><br><br>
                </div>

                <div class="form-actions">
                    <button type="submit">Update</button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>