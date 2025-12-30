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

$loggedRole   = $_SESSION['user']['role_name'];
$loggedUserId = $_SESSION['user']['id'];

$assignableUsers = [];

foreach ($users as $user) {

    if ($user['id'] == $loggedUserId) {
        continue;
    }

    if (
        $loggedRole === 'Manager' &&
        in_array($user['role_name'], ['Super Admin', 'Admin', 'Manager'])
    ) {
        continue;
    }

    if (
        $loggedRole === 'Admin' &&
        in_array($user['role_name'], ['Super Admin', 'Admin', 'Manager'])
    ) {
        continue;
    }

    if (
        $loggedRole === 'Super Admin' &&
        in_array($user['role_name'], ['Super Admin', 'Admin', 'Manager'])
    ) {
        continue;
    }

    $assignableUsers[] = $user;
}

$old    = $_SESSION['old'] ?? [];
$errors = $_SESSION['errors'] ?? [];

unset($_SESSION['old'], $_SESSION['errors']);

$title       = $old['title'] ?? '';
$description = $old['description'] ?? '';
$assignedTo  = $old['assigned_to'] ?? '';
$startDate   = $old['start_date'] ?? '';
$endDate     = $old['end_date'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $assignedTo  = $_POST['assigned_to'] ?? null;
    $assignedBy  = $loggedUserId;
    $startDate   = $_POST['start_date'] ?? null;
    $endDate     = $_POST['end_date'] ?? null;

    $errors = [];

    // VALIDATIONS
    if ($title === '') {
        $errors[] = 'Task title is required';
    }

    if (!$assignedTo) {
        $errors[] = 'Please select a user';
    }

    if (!$startDate || !$endDate) {
        $errors[] = 'Start date and End date are required';
    }

    if ($startDate && $endDate && $startDate > $endDate) {
        $errors[] = 'End date cannot be before start date';
    }

    // IF ERRORS → REDIRECT BACK
    if ($errors) {
        $_SESSION['old']    = $_POST;
        $_SESSION['errors'] = $errors;
        header('Location: add.php');
        exit;
    }

    // CREATE TASK
    $result = $taskService->createTask(
        $title,
        $description,
        $assignedTo,
        $assignedBy,
        $startDate,
        $endDate
    );

    if (!$result['success']) {
        $_SESSION['old']    = $_POST;
        $_SESSION['errors'] = $result['errors'];
        header('Location: add.php');
        exit;
    }

    header('Location: list.php');
    exit;
}

$pagetitle = 'Assign Task';
require_once '../../includes/header.php';
?>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Assign Task</h1>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="POST" novalidate>

                <div class="form-group">
                    <label>Task Title</label>
                    <input type="text"
                        name="title"
                        class="form-control"
                        value="<?= htmlspecialchars($title) ?>">
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description"
                        class="form-control"
                        rows="4"><?= htmlspecialchars($description) ?></textarea>
                </div>

                <div class="form-group">
                    <label>Assign To</label>
                    <select name="assigned_to" class="form-control">
                        <option value="">-- Select User --</option>
                        <?php foreach ($assignableUsers as $user): ?>
                            <option value="<?= $user['id'] ?>"
                                <?= ($assignedTo == $user['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars(
                                    $user['first_name'] . ' ' . $user['last_name']
                                ) ?>
                                (<?= htmlspecialchars($user['email']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-2">
                        <label>Start Date</label>
                        <input type="date"
                            name="start_date"
                            class="form-control"
                            value="<?= htmlspecialchars($startDate) ?>">
                    </div>

                    <div class="col-md-2">
                        <label>End Date</label>
                        <input type="date"
                            name="end_date"
                            class="form-control"
                            value="<?= htmlspecialchars($endDate) ?>">
                    </div>
                </div>

                <div class="mt-3">
                    <button class="btn btn-primary">
                        Assign Task
                    </button>

                    <a href="list.php"
                        class="btn btn-secondary ml-2">
                        Cancel
                    </a>
                </div>

            </form>

        </div>
    </div>

</div>

<?php require_once '../../includes/footer.php'; ?>