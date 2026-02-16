<?php
require_once '../../includes/repo/auth.php';
requireLogin();
requireRole(['Super Admin']);

require_once '../../config/database.php';
require_once '../../includes/services/RoleService.php';

// db connection
$dbObj = new Database();
$db = $dbObj->getConnection();

// role service
$roleService = new RoleService($db);

// field errors
$fieldErrors = [];

// preserve values
$name = '';
$status = '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name   = trim($_POST['name'] ?? '');
    $status = $_POST['status'] ?? '1';

    /* -----------------------------
       SERVER-SIDE VALIDATION
    ------------------------------*/

    if ($name === '') {
        $fieldErrors['name'] = 'Role name is required';
    }

    /* -----------------------------
       CREATE ROLE IF VALID
    ------------------------------*/
    if (empty($fieldErrors)) {

        // generate slug internally
        $slug = $roleService->generateSlug($name);

        $result = $roleService->createRole($name, $slug, $status);

        if ($result['success']) {

            $_SESSION['swal'] = [
                'icon'  => 'success',
                'title' => 'Role Created',
                'text'  => $result['message']
            ];

            header('Location: list.php');
            exit();
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
$title = 'Add Role';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Add Role</h1>

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

                <!-- Slug Info -->
                <div class="form-group">
                    <label>Slug</label>
                    <input type="text"
                        class="form-control"
                        value="Auto-generated from role name"
                        disabled>
                    <small class="text-muted">
                        Slug will be generated automatically.
                    </small>
                </div>

                <!-- Status -->

                <!-- Actions -->
                <button type="submit" class="btn btn-primary btn-sm">
                    Create Role
                </button>

                <a href="list.php" class="btn btn-secondary btn-sm">
                    Cancel
                </a>

            </form>

        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php';
 ?>