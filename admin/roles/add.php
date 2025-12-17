<?php
require_once '../../includes/auth.php';
requireLogin();
requireRole(['Super Admin', 'Admin']);
require_once '../../config/database.php';
require_once '../../includes/roleService.php';


// db connection
$dbObj = new Database();
$db = $dbObj->getConnection();

// role service
$roleService = new RoleService($db);

$errors = [];
$successMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name']);
    $status = $_POST['status'];

    // generate slug internally
    $slug = $roleService->generateSlug($name);

    $result = $roleService->createRole($name, $slug, $status);

    if ($result['success']) {
        $successMsg = $result['message'];
    } else {
        $errors = $result['errors'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Role</title>
    <style>
        body {
            font-family: Arial;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        input, select {
            padding: 8px;
            width: 300px;
        }
        .btn {
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 4px;
            background: #2ecc71;
            color: #fff;
            border: none;
        }
        .error {
            color: red;
        }
        .success {
            color: green;
        }
    </style>
</head>
<body>

<h2>Add Role</h2>

<a href="list.php">← Back to list</a>
<br><br>

<?php if (!empty($errors)) { ?>
    <div class="error">
        <ul>
            <?php foreach ($errors as $error) { ?>
                <li><?php echo $error; ?></li>
            <?php } ?>
        </ul>
    </div>
<?php } ?>

<?php if ($successMsg) { ?>
    <div class="success"><?php echo $successMsg; ?></div>
<?php } ?>

<form method="post">

    <div class="form-group">
        <label>Role Name</label><br>
        <input type="text" name="name" required>
    </div>

    <div class="form-group">
        <label>Status</label><br>
        <select name="status">
            <option value="1">Active</option>
            <option value="0">Inactive</option>
        </select>
    </div>

    <button type="submit" class="btn">Save Role</button>

</form>

<?php require_once '../../includes/footer.php'; ?>
</body>
</html>
