<?php
require_once '../../includes/repo/auth.php';
requireLogin();
requireRole(['Admin', 'Super Admin', 'Manager']);

require_once '../../config/database.php';
require_once '../../includes/services/TaskService.php';

$db = (new Database())->getConnection();
$taskService = new TaskService($db);

// Admin / Super Admin can view all tasks
$tasks = $taskService->getAllTasks();

$pageTitle = 'Tasks Overview';
require_once '../../includes/header.php';
?>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Tasks Overview</h1>

    <?php if (empty($tasks)): ?>
        <div class="alert alert-info">
            No tasks found.
        </div>
    <?php else: ?>

        <div class="card shadow-sm">
            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Task</th>
                                <th>Assigned By</th>
                                <th>Assigned To</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($tasks as $task): ?>
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

                                    <td>
                                        <?= htmlspecialchars($task['assigned_by_email']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($task['assigned_to_email']) ?>
                                    </td>

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

                                    <td>
                                        <?= date('d M Y, h:i A', strtotime($task['created_at'])) ?>
                                    </td>
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