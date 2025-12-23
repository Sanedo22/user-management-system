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
?>
<!DOCTYPE html>
<html>

<head>
    <title>Add Role</title>
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
        <h2>Add Role</h2>

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
                    <option value="1" <?= ($status == '1') ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= ($status == '0') ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn primary">Save Role</button>
            </div>
        </form>

        <?php require_once '../../includes/footer.php'; ?>
    </div>
</div>

</body>
</html>
