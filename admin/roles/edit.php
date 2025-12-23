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
?>
<!DOCTYPE html>
<html>

<head>
    <title>Edit Role</title>
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

        <h2>Edit Role</h2>

        <a href="list.php">← Back to list</a>
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
                <label>Role Name</label><br>
                <input type="text" name="name" value="<?= htmlspecialchars($name) ?>">
                <?php if (isset($fieldErrors['name'])): ?>
                    <div class="field-error"><?= htmlspecialchars($fieldErrors['name']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Status</label><br>
                <select name="status">
                    <option value="1" <?= ($status == 1) ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= ($status == 0) ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn">Update Role</button>
            </div>
        </form>

    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
</body>

</html>
