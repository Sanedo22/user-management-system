<?php
require_once '../../includes/repo/auth.php';
requireLogin();

require_once '../../config/database.php';
require_once '../../includes/services/TaskService.php';

$db = (new Database())->getConnection();
$taskService = new TaskService($db);

$loggedUserId = $_SESSION['user']['id'];
$loggedRole   = $_SESSION['user']['role_name'];

// Tasks assigned TO me (everyone except SA)
$tasksAssignedToMe = [];
if ($loggedRole !== 'Super Admin') {
    $tasksAssignedToMe = $taskService->getTasksForUser($loggedUserId);
}

// Tasks assigned BY me (Manager / Super Admin)
$tasksAssignedByMe = [];
if (in_array($loggedRole, ['Manager', 'Super Admin'])) {
    $tasksAssignedByMe = $taskService->getTasksAssignedBy($loggedUserId);
}

$today = date('Y-m-d');

$pagetitle = 'Tasks';
require_once '../../includes/header.php';
?>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Tasks</h1>

    <?php if (in_array($loggedRole, ['Manager', 'Super Admin', 'Admin'])): ?>
        <div class="mb-3">
            <a href="add.php" class="btn btn-primary btn-sm">
                + Assign Task
            </a>
        </div>
    <?php endif; ?>

    <!-- ===================== TASKS ASSIGNED TO ME ===================== -->
    <?php if ($loggedRole !== 'Super Admin'): ?>
        <h5 class="text-primary mb-3">Tasks Assigned To Me</h5>

        <?php if (empty($tasksAssignedToMe)): ?>
            <div class="alert alert-info">No tasks assigned to you.</div>
        <?php else: ?>
            <div class="card shadow-sm mb-5">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="assignedToMeTable"
                            class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Task</th>
                                    <th>Assigned By</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tasksAssignedToMe as $task): ?>

                                    <?php
                                    $isOverdue = (
                                        $task['status'] !== 'Completed' &&
                                        $today > $task['end_date']
                                    );
                                    ?>

                                    <tr class="<?= $isOverdue ? 'table-danger' : '' ?>">
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

                                        <td><?= date('d M Y', strtotime($task['start_date'])) ?></td>
                                        <td><?= date('d M Y', strtotime($task['end_date'])) ?></td>

                                        <td>
                                            <form method="POST"
                                                action="update_status.php"
                                                class="d-inline">
                                                <input type="hidden" name="task_id" value="<?= $task['id'] ?>">

                                                <select name="status"
                                                    class="form-control form-control-sm"
                                                    onchange="this.form.submit()"
                                                    <?= ($task['status'] === 'Completed') ? 'disabled' : '' ?>>

                                                    <?php foreach (['Pending', 'In Progress', 'Completed'] as $s): ?>
                                                        <option value="<?= $s ?>"
                                                            <?= ($task['status'] === $s) ? 'selected' : '' ?>>
                                                            <?= $s ?>
                                                        </option>
                                                    <?php endforeach; ?>

                                                </select>
                                            </form>

                                            <?php if ($isOverdue): ?>
                                                <span class="badge badge-danger mt-1">Overdue</span>
                                            <?php endif; ?>
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
                    <table id="assignedByMeTable"
                        class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Task</th>
                                <th>Assigned To</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasksAssignedByMe as $task): ?>

                                <?php
                                $isOverdue = (
                                    $task['status'] !== 'Completed' &&
                                    $today > $task['end_date']
                                );

                                switch ($task['status']) {
                                    case 'Completed':
                                        $badge = 'success';
                                        $label = 'Completed';
                                        break;

                                    case 'In Progress':
                                        $badge = 'warning';
                                        $label = 'In Progress';
                                        break;

                                    case 'Pending':
                                    default:
                                        $badge = 'secondary';
                                        $label = 'Pending';
                                        break;
                                }

                                // Optional visual override for overdue (does NOT change status)
                                if ($isOverdue && $task['status'] !== 'Completed') {
                                    $badge = 'danger';
                                }
                                ?>


                                <tr class="<?= $isOverdue ? 'table-danger' : '' ?>">
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

                                    <td><?= date('d M Y', strtotime($task['start_date'])) ?></td>
                                    <td><?= date('d M Y', strtotime($task['end_date'])) ?></td>

                                    <td>
                                        <span class="badge badge-<?= $badge ?>">
                                            <?= $label ?>
                                        </span>
                                    </td>

                                    <td><?= date('d M Y, h:i A', strtotime($task['created_at'])) ?></td>

                                    <td>
                                        <a href="edit.php?id=<?= $task['id'] ?>"
                                            class="btn btn-sm btn-warning">
                                            Edit
                                        </a>

                                        <a href="delete.php?id=<?= $task['id'] ?>"
                                            class="btn btn-sm btn-danger delete-task">
                                            Delete
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

        if ($('#assignedToMeTable').length) {
            $('#assignedToMeTable').DataTable();
        }

        if ($('#assignedByMeTable').length) {
            $('#assignedByMeTable').DataTable();
        }

    });
    document.addEventListener('DOMContentLoaded', function() {

        document.querySelectorAll('.delete-task').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();

                // SAFETY CHECK
                if (typeof swalConfirm !== 'function') {
                    // fallback
                    window.location.href = this.href;
                    return;
                }

                swalConfirm({
                    title: 'Delete task?',
                    text: 'This task will be moved to deleted tasks.',
                    confirmText: 'Delete',
                    confirmColor: '#d33'
                }).then(result => {
                    if (result.isConfirmed) {
                        window.location.href = this.href;
                    }
                });
            });
        });

    });
</script>