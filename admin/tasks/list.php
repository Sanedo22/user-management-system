<?php
require_once '../../includes/repo/auth.php';
requireLogin();
// requireRole(['User', 'Manager', 'Super Admin', 'Admin' ]);

require_once '../../config/database.php';
require_once '../../includes/services/TaskService.php';

$db = (new Database())->getConnection();
$taskService = new TaskService($db);

$loggedUserId = $_SESSION['user']['id'];
$loggedRole   = $_SESSION['user']['role_name'];

// Tasks assigned TO me (everyone)
$tasksAssignedToMe = [];
if ($_SESSION['user']['role_name'] !== 'Super Admin') {
    $tasksAssignedToMe = $taskService->getTasksForUser($loggedUserId);
}

// Tasks assigned BY me (Manager / Super Admin only)
$tasksAssignedByMe = [];
if (in_array($loggedRole, ['Manager', 'Super Admin'])) {
    $tasksAssignedByMe = $taskService->getTasksAssignedBy($loggedUserId);
}

$pagetitle = 'Tasks';
require_once '../../includes/header.php';
?>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Tasks</h1>

    <?php if (in_array($_SESSION['user']['role_name'], ['Manager', 'Super Admin', 'Admin'])): ?>
        <div class="mb-3">
            <a href="add.php" class="btn btn-primary btn-sm">
                + Assign Task
            </a>
        </div>
    <?php endif; ?>

    <!-- ===================== TASKS ASSIGNED TO ME ===================== -->

    <?php if ($_SESSION['user']['role_name'] !== 'Super Admin'): ?>
        <h5 class="text-primary mb-3">Tasks Assigned To Me</h5>

        <?php if (empty($tasksAssignedToMe)): ?>
            <div class="alert alert-info">No tasks assigned to you.</div>
        <?php else: ?>
            <div class="card shadow-sm mb-5">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Task</th>
                                    <th>Assigned By</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tasksAssignedToMe as $task): ?>
                                    <tr>
                                        <td><?= $task['id'] ?></td>

                                        <td>
                                            <strong><?= htmlspecialchars($task['title']) ?></strong>
                                            <?php if (!empty($task['description'])): ?>
                                                <div class="text-muted small">
                                                    <?= nl2br(htmlspecialchars($task['description'])) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <td><?= htmlspecialchars($task['assigned_by_email']) ?></td>

                                        <td>
                                            <?php
                                            $statusClass = match ($task['status']) {
                                                'Pending' => 'badge-secondary',
                                                'In Progress' => 'badge-warning',
                                                'Completed' => 'badge-success',
                                                default => 'badge-light'
                                            };
                                            ?>
                                            <span class="badge <?= $statusClass ?>">
                                                <?= $task['status'] ?>
                                            </span>
                                        </td>

                                        <td><?= date('d M Y, h:i A', strtotime($task['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>


    <!-- ===================== TASKS ASSIGNED BY ME ===================== -->
    <?php if (!empty($tasksAssignedByMe)): ?>
        <h5 class="text-secondary mb-3">Tasks Assigned By Me</h5>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Task</th>
                                <th>Assigned To</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasksAssignedByMe as $task): ?>
                                <tr>
                                    <td><?= $task['id'] ?></td>

                                    <td>
                                        <strong><?= htmlspecialchars($task['title']) ?></strong>
                                        <?php if (!empty($task['description'])): ?>
                                            <div class="text-muted small">
                                                <?= nl2br(htmlspecialchars($task['description'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td><?= htmlspecialchars($task['assigned_to_email']) ?></td>

                                    <td>
                                        <?php
                                        $statusClass = match ($task['status']) {
                                            'Pending' => 'badge-secondary',
                                            'In Progress' => 'badge-warning',
                                            'Completed' => 'badge-success',
                                            default => 'badge-light'
                                        };
                                        ?>
                                        <span class="badge <?= $statusClass ?>">
                                            <?= $task['status'] ?>
                                        </span>
                                    </td>

                                    <td><?= date('d M Y, h:i A', strtotime($task['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php require_once '../../includes/footer.php'; ?>