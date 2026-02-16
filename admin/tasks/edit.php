<?php
require_once '../../includes/repo/auth.php';
requireLogin();
requireRole(['Admin', 'Manager', 'Super Admin']);

require_once '../../config/database.php';
require_once '../../includes/services/TaskService.php';

$db = (new Database())->getConnection();
$taskService = new TaskService($db);

/* ================================
   GET TASK
================================ */
$taskId = $_GET['id'] ?? null;

if (!$taskId) {
    header('Location: list.php');
    exit;
}

$task = $taskService->getTaskById($taskId);

if (!$task) {
    header('Location: list.php');
    exit;
}

/* ================================
   OLD INPUT
================================ */
$old = $_SESSION['old'] ?? [];
unset($_SESSION['old']);

$title       = $old['title']       ?? $task['title'];
$description = $old['description'] ?? $task['description'];
$startDate   = $old['start_date']  ?? $task['start_date'];
$endDate     = $old['end_date']    ?? $task['end_date'];

$errors = [];

/* ================================
   FORM SUBMIT
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $startDate   = $_POST['start_date'] ?? null;
    $endDate     = $_POST['end_date'] ?? null;

    // VALIDATIONS
    if ($title === '') {
        $errors[] = 'Task title is required';
    }

    if (!$startDate || !$endDate) {
        $errors[] = 'Start date and End date are required';
    }

    if ($startDate && $endDate && $startDate > $endDate) {
        $errors[] = 'End date cannot be before start date';
    }

    // IF ERRORS → REDIRECT BACK
    if ($errors) {
        $_SESSION['old'] = $_POST;
        $_SESSION['errors'] = $errors;
        header('Location: edit.php?id=' . $taskId);
        exit;
    }

    // UPDATE TASK
    $result = $taskService->updateTask(
        $taskId,
        $title,
        $description,
        $startDate,
        $endDate
    );

    if (!$result['success']) {
        $_SESSION['errors'] = $result['errors'];
        header('Location: edit.php?id=' . $taskId);
        exit;
    }

    header('Location: list.php');
    exit;
}

/* ================================
   ERRORS FROM SESSION
================================ */
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']);

$pagetitle = 'Edit Task';
require_once '../../includes/header.php';
?>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Edit Task</h1>

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
                        Update Task
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
