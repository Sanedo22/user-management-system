<?php
require_once '../../includes/repo/auth.php';
requireLogin();
requireRole(['Manager', 'Super Admin', 'Admin']);

require_once '../../config/database.php';
require_once '../../includes/services/TaskService.php';
require_once '../../includes/services/UserService.php';

$db = (new Database())->getConnection();

$taskService = new TaskService($db);
$userService = new UserService($db);

$users = $userService->getAllUsers(false);

$loggedRole = $_SESSION['user']['role_name'];
$loggedUserId = $_SESSION['user']['id'];

$assignableUsers = [];

foreach ($users as $user) {

    $targetRole = strtolower(trim($user['role_name'] ?? ''));

    // cannot assign to self
    if ($user['id'] == $loggedUserId) {
        continue;
    }

    // Manager -> User only
    if ($loggedRole === 'Manager' && in_array($user['role_name'], ['Super Admin', 'Admin', 'Manager'])) {
        continue;
    }

    // Admin -> Manager and users only
    if ($loggedRole === 'Admin' && in_array($user['role_name'], ['Super Admin', 'Admin',])) {
        continue;
    }

    // Super Admin → cannot assign to Super Admin
    if ($loggedRole === 'Super Admin' && $user['role_name'] === 'Super Admin') {
        continue;
    }

    $assignableUsers[] = $user;
}


$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $assignedTo = $_POST['assigned_to'] ?? null;
    $assignedBy = $_SESSION['user']['id'];

    if (!$assignedTo) {
        $errors[] = 'Please select a user';
    }

    if (empty($errors)) {
        $result = $taskService->createTask(
            $title,
            $description,
            $assignedTo,
            $assignedBy
        );

        if ($result['success']) {
            $success = $result['message'];
        } else {
            $errors = $result['errors'];
        }
    }
}

$pagetitle = 'Assign Task';
require_once '../../includes/header.php';
?>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Assign Task</h1>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success position-relative">
            <?= htmlspecialchars($success) ?>
            <button class="alert-close-btn"
                onclick="this.closest('.alert').remove()">&times;</button>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger position-relative">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <button class="alert-close-btn"
                onclick="this.closest('.alert').remove()">&times;</button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="POST">

                <div class="form-group">
                    <label>Task Title</label>
                    <input type="text"
                        name="title"
                        class="form-control"
                        required>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description"
                        class="form-control"
                        rows="4"></textarea>
                </div>

                <div class="form-group">
                    <label>Assign To</label>
                    <select name="assigned_to"
                        class="form-control"
                        required>
                        <option value="">-- Select User --</option>
                        <?php foreach ($assignableUsers as $user): ?>
                            <option value="<?= $user['id'] ?>">
                                <?= htmlspecialchars(
                                    $user['first_name'] . ' ' . $user['last_name']
                                ) ?>
                                (<?= $user['email'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button class="btn btn-primary">
                    Assign Task
                </button>

                <a href="../dashboard.php"
                    class="btn btn-secondary ml-2">
                    Cancel
                </a>

            </form>

        </div>
    </div>

</div>

<?php require_once '../../includes/footer.php'; ?>