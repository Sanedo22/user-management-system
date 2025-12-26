<?php
require_once '../../includes/repo/auth.php';
requireLogin();
requireRole(['Super Admin']);

require_once '../../config/database.php';
require_once '../../includes/services/RoleService.php';

// validate id
if (!isset($_GET['id'])) {
    header('Location: list.php');
    exit;
}

$id = $_GET['id'];

// db connection
$dbObj = new Database();
$db = $dbObj->getConnection();

// role service
$roleService = new RoleService($db);

// get existing role
$role = $roleService->getRole($id);
if (!$role) {
    header('Location: list.php');
    exit;
}

if ($roleService->isRoleAssignedToUsers($id)) {
    $_SESSION['swal'] = [
        'icon'  => 'warning',
        'title' => 'Role In Use',
        'text'  => 'This role is already assigned to users and cannot be edited.'
    ];
    header('Location: list.php');
    exit;
}


// protect Super Admin role
if ($role['name'] === 'Super Admin') {
    $_SESSION['swal'] = [
        'icon'  => 'error',
        'title' => 'Action Not Allowed',
        'text'  => 'Super Admin role cannot be modified'
    ];
    header('Location: list.php');
    exit;
}

// field errors
$fieldErrors = [];

// preserve values
$name   = $role['name'];
$status = $role['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name   = trim($_POST['name'] ?? '');
    $status = $_POST['status'] ?? $role['status'];

    if ($name === '') {
        $fieldErrors['name'] = 'Role name is required';
    }

    if (empty($fieldErrors)) {

        // regenerate slug internally
        $slug = $roleService->generateSlug($name);

        $result = $roleService->updateRole($id, $name, $slug, $status);

        if ($result['success']) {

            $_SESSION['swal'] = [
                'icon'  => 'success',
                'title' => 'Role Updated',
                'text'  => $result['message']
            ];

            header('Location: list.php');
            exit;
        } else {
            foreach ($result['errors'] as $error) {
                if (stripos($error, 'name') !== false) {
                    $fieldErrors['name'] = $error;
                } else {
                    $fieldErrors['general'][] = $error;
                }
            }
        }
    }
}

$title = 'Edit Role';
require_once '../../includes/header.php';
?>

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Role</h1>

        <a href="list.php" class="btn btn-dark btn-sm">
            Back to Roles
        </a>
    </div>

    <?php if (!empty($fieldErrors['general'])): ?>
        <div class="alert alert-danger">
            <?php foreach ($fieldErrors['general'] as $err): ?>
                <?= htmlspecialchars($err) ?><br>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm col-lg-6 p-0">
        <div class="card-body">

            <form method="post">

                <!-- Role Name -->
                <div class="form-group">
                    <label>Role Name</label>
                    <input type="text"
                        name="name"
                        class="form-control"
                        value="<?= htmlspecialchars($name) ?>">
                    <?php if (isset($fieldErrors['name'])): ?>
                        <small class="text-danger"><?= htmlspecialchars($fieldErrors['name']) ?></small>
                    <?php endif; ?>
                </div>

                <!-- Slug (Info only) -->
                <div class="form-group">
                    <label>Slug</label>
                    <input type="text"
                        class="form-control"
                        value="Auto-generated from role name"
                        disabled>
                    <small class="text-muted">
                        Slug will be regenerated automatically if name changes.
                    </small>
                </div>

                <!-- Actions -->
                <button type="submit" class="btn btn-primary btn-sm">
                    Update Role
                </button>

                <a href="list.php" class="btn btn-secondary btn-sm">
                    Cancel
                </a>

            </form>

        </div>
    </div>

</div>

<?php require_once '../../includes/footer.php';
?>