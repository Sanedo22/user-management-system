<?php
require_once '../../includes/auth.php';
requireLogin();
requireRole(['Super Admin']);
require_once '../../config/database.php';
require_once '../../includes/roleService.php';


// check id
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

$errors = [];
$successMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name']);
    $status = $_POST['status'];

    // regenerate slug internally
    $slug = $roleService->generateSlug($name);

    $result = $roleService->updateRole($id, $name, $slug, $status);

    if ($result['success']) {
        $successMsg = $result['message'];
        // refresh data
        $role = $roleService->getRole($id);
    } else {
        $errors = $result['errors'];
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Edit Role</title>
    <link rel="stylesheet" href="../../assets/css/form.css">
</head>

<body>

    <div class="form-container">
        <div class="form-card">

            <h2>Edit Role</h2>

            <a href="list.php">← Back to list</a>
            <br><br>

            <?php if (!empty($errors)) { ?>
                <div class="alert error">
                    <ul>
                        <?php foreach ($errors as $error) { ?>
                            <li><?php echo $error; ?></li>
                        <?php } ?>
                    </ul>
                </div>
            <?php } ?>

            <?php if ($successMsg) { ?>
                <div class="alert success"><?php echo $successMsg; ?></div>
            <?php } ?>

            <form method="post">

                <div class="form-group">
                    <label>Role Name</label><br>
                    <input type="text" name="name"
                        value="<?php echo htmlspecialchars($role['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Status</label><br>
                    <select name="status">
                        <option value="1" <?php if ($role['status'] == 1) echo 'selected'; ?>>
                            Active
                        </option>
                        <option value="0" <?php if ($role['status'] == 0) echo 'selected'; ?>>
                            Inactive
                        </option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn">Update Role</button>
                </div>
            </form>
        </div>
</body>

</html>