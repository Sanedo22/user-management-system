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
$title = 'Edit User';
require_once '../../includes/header.php';
?>

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit User</h1>

        <a href="list.php" class="btn btn-dark btn-sm">
            Back to Users
        </a>
    </div>

    <?php if (!empty($fieldErrors['general'])): ?>
        <div class="alert alert-danger">
            <?php foreach ($fieldErrors['general'] as $err): ?>
                <?= htmlspecialchars($err) ?><br>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm col-lg-8 p-0">
        <div class="card-body">

            <form method="post">

                <!-- Role -->
                <div class="form-group">
                    <label>Role</label>
                    <select name="role_id" class="form-control">
                        <option value="">-- Select Role --</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['id'] ?>"
                                <?= ($role_id == $role['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($role['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($fieldErrors['role_id'])): ?>
                        <small class="text-danger"><?= htmlspecialchars($fieldErrors['role_id']) ?></small>
                    <?php endif; ?>
                </div>

                <!-- Name -->
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>First Name</label>
                        <input type="text"
                            name="firstname"
                            class="form-control"
                            value="<?= htmlspecialchars($firstname) ?>">
                        <?php if (isset($fieldErrors['firstname'])): ?>
                            <small class="text-danger"><?= htmlspecialchars($fieldErrors['firstname']) ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group col-md-6">
                        <label>Last Name</label>
                        <input type="text"
                            name="lastname"
                            class="form-control"
                            value="<?= htmlspecialchars($lastname) ?>">
                        <?php if (isset($fieldErrors['lastname'])): ?>
                            <small class="text-danger"><?= htmlspecialchars($fieldErrors['lastname']) ?></small>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label>Email</label>
                    <input type="email"
                        name="email"
                        class="form-control"
                        value="<?= htmlspecialchars($email) ?>" readonly>
                    <?php if (isset($fieldErrors['email'])): ?>
                        <small class="text-danger"><?= htmlspecialchars($fieldErrors['email']) ?></small>
                    <?php endif; ?>
                </div>

                <!-- Phone -->
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label>Country Code</label>
                        <select name="country_code" class="form-control">
                            <option value="+91" <?= ($countrycode === '+91') ? 'selected' : '' ?>>+91</option>
                            <option value="+1" <?= ($countrycode === '+1')  ? 'selected' : '' ?>>+1</option>
                            <option value="+44" <?= ($countrycode === '+44') ? 'selected' : '' ?>>+44</option>
                        </select>
                    </div>

                    <div class="form-group col-md-9">
                        <label>Phone Number</label>
                        <input type="text"
                            name="phone_number"
                            class="form-control"
                            value="<?= htmlspecialchars($phonenumber) ?>">
                        <?php if (isset($fieldErrors['phone_number'])): ?>
                            <small class="text-danger"><?= htmlspecialchars($fieldErrors['phone_number']) ?></small>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Address -->
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address"
                        class="form-control"
                        rows="3"><?= htmlspecialchars($address) ?></textarea>
                </div>

                <!-- Actions -->
                <button type="submit" class="btn btn-primary btn-sm">
                    Update User
                </button>

                <a href="list.php" class="btn btn-secondary btn-sm">
                    Cancel
                </a>

            </form>

        </div>
    </div>

</div>

<?php require_once '../../includes/footer.php'; ?>