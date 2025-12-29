<?php
require_once '../../includes/repo/auth.php';
requireLogin();
requireRole(['Admin', 'Super Admin', 'Manager']);

require_once '../../config/database.php';
require_once '../../includes/services/TaskService.php';

$db = (new Database())->getConnection();
$taskService = new TaskService($db);

$today = date('Y-m-d');

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
                    <table id="overviewTasksTable"
                        class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Task</th>
                                <th>Assigned By</th>
                                <th>Assigned To</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($tasks as $task): ?>

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

                                    <td>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-info view-task"

                                            data-title="<?= htmlspecialchars($task['title']) ?>"
                                            data-desc="<?= htmlspecialchars($task['description']) ?>"
                                            data-assigned-to="<?= htmlspecialchars($task['assigned_to_email']) ?>"
                                            data-assigned-by="<?= htmlspecialchars($task['assigned_by_email']) ?>"
                                            data-status="<?= htmlspecialchars($task['status']) ?>">
                                            View
                                        </button>
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

<!-- View Task Modal -->
<div class="modal fade" id="viewTaskModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Task Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    &times;
                </button>
            </div>

            <div class="modal-body">

                <div class="mb-2">
                    <strong>Title:</strong>
                    <div id="mTitle" class="text-dark"></div>
                </div>

                <div class="mb-2">
                    <strong>Description:</strong>
                    <div id="mDesc" class="text-muted"></div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-sm-6 mb-2">
                        <strong>Assigned To:</strong>
                        <div id="mAssignedTo"></div>
                    </div>

                    <div class="col-sm-6 mb-2">
                        <strong>Assigned By:</strong>
                        <div id="mAssignedBy"></div>
                    </div>
                </div>

                <div class="mt-2">
                    <strong>Status:</strong>
                    <span id="mStatus" class="badge badge-secondary"></span>
                </div>

            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-dismiss="modal">
                    Close
                </button>
            </div>

        </div>
    </div>
</div>



<?php require_once '../../includes/footer.php'; ?>

<script>
    $(function() {
        if ($('#overviewTasksTable').length) {
            $('#overviewTasksTable').DataTable({
                order: [
                    [0, 'desc']
                ],
                pageLength: 10
            });
        }
    });

    $(function() {

        $('.view-task').on('click', function() {

            const status = $(this).data('status');

            $('#mTitle').text($(this).data('title'));
            $('#mDesc').text($(this).data('desc') || '-');
            $('#mAssignedTo').text($(this).data('assigned-to'));
            $('#mAssignedBy').text($(this).data('assigned-by'));
            $('#mStatus').text(status);

            // Reset badge classes
            $('#mStatus')
                .removeClass('badge-success badge-warning badge-secondary')
                .addClass(
                    status === 'Completed' ? 'badge-success' :
                    status === 'In Progress' ? 'badge-warning' :
                    'badge-secondary'
                );

            $('#viewTaskModal').modal('show');
        });

    });
</script>