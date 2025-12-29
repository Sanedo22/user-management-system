<?php
require_once '../../includes/repo/auth.php';
requireLogin();
requireRole(['Super Admin']);

require_once '../../config/database.php';
require_once '../../includes/services/TaskService.php';

$db = (new Database())->getConnection();
$taskService = new TaskService($db);

$tasks = $taskService->getDeletedTasks();

require_once '../../includes/header.php';
?>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Deleted Tasks</h1>

    <div class="mb-3">
    <a href="list.php" class="btn btn-primary btn-sm">Back to list</a>
    </div>

    <?php if (empty($tasks)): ?>
        <div class="alert alert-info">
            No deleted tasks found.
        </div>
    <?php else: ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="deletedTasksTable"
                        class="table table-bordered table-hover">

                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Task</th>
                                <th>Assigned To</th>
                                <th>Assigned By</th>
                                <th>Deleted At</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td><?= $task['id'] ?></td>
                                    <td><?= htmlspecialchars($task['title']) ?></td>
                                    <td><?= htmlspecialchars($task['assigned_to_email']) ?></td>
                                    <td><?= htmlspecialchars($task['assigned_by_email']) ?></td>
                                    <td><?= date('d M Y, h:i A', strtotime($task['deleted_at'])) ?></td>
                                    <td>
                                        <a href="restore.php?id=<?= $task['id'] ?>"
                                            class="btn btn-sm btn-success restore-task">
                                            Restore
                                        </a>

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

<script>
    $(function() {
        $('#deletedTasksTable').DataTable();
    });

    document.addEventListener('DOMContentLoaded', function() {

        document.querySelectorAll('.restore-task').forEach(btn => {

            btn.addEventListener('click', function(e) {
                e.preventDefault();

                swalConfirm({
                    title: 'Restore task?',
                    text: 'This task will be restored and visible again.',
                    icon: 'question',
                    confirmText: 'Restore',
                    confirmColor: '#28a745'
                }).then(result => {
                    if (result.isConfirmed) {
                        window.location.href = this.href;
                    }
                });
            });

        });

    });
</script>