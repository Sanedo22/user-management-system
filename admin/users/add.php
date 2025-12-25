<?php
require_once '../../includes/repo/auth.php';
requireLogin();
requireRole(['Super Admin', 'Admin']);

require_once '../../config/database.php';
require_once '../../includes/services/UserService.php';
require_once '../../includes/services/RoleService.php';

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
$title = 'Add User';
require_once '../../includes/header.php';
?>

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Add User</h1>

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
                        <input type="text" name="firstname"
                               class="form-control"
                               value="<?= htmlspecialchars($firstname) ?>">
                        <?php if (isset($fieldErrors['firstname'])): ?>
                            <small class="text-danger"><?= htmlspecialchars($fieldErrors['firstname']) ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group col-md-6">
                        <label>Last Name</label>
                        <input type="text" name="lastname"
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
                    <input type="email" name="email"
                           class="form-control"
                           value="<?= htmlspecialchars($email) ?>">
                    <?php if (isset($fieldErrors['email'])): ?>
                        <small class="text-danger"><?= htmlspecialchars($fieldErrors['email']) ?></small>
                    <?php endif; ?>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control">
                    <?php if (isset($fieldErrors['password'])): ?>
                        <small class="text-danger"><?= htmlspecialchars($fieldErrors['password']) ?></small>
                    <?php endif; ?>
                </div>

                <!-- Phone -->
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label>Country Code</label>
                        <select name="country_code" class="form-control">
                            <option value="">-- Code --</option>
                            <option value="+91" <?= ($countrycode === '+91') ? 'selected' : '' ?>>+91</option>
                            <option value="+1" <?= ($countrycode === '+1') ? 'selected' : '' ?>>+1</option>
                            <option value="+44" <?= ($countrycode === '+44') ? 'selected' : '' ?>>+44</option>
                        </select>
                    </div>

                    <div class="form-group col-md-9">
                        <label>Phone Number</label>
                        <input type="text" name="phone_number"
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
                    Create User
                </button>

                <a href="list.php" class="btn btn-secondary btn-sm">
                    Cancel
                </a>

            </form>

        </div>
    </div>

</div>

<?php require_once '../../includes/footer.php'; ?>
