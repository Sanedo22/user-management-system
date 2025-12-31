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

// Tasks assigned BY me (Manager / Super Admin / Admin)
$tasksAssignedByMe = [];
if (in_array($loggedRole, ['Manager', 'Super Admin', 'Admin'])) {
    $tasksAssignedByMe = $taskService->getTasksAssignedBy($loggedUserId);
}

// load comments
$taskComments = [];
foreach ($tasksAssignedToMe as $t) {
    $taskComments[$t['id']] = $taskService->getCommentsForTask($t['id']);
}
foreach ($tasksAssignedByMe as $t) {
    $taskComments[$t['id']] = $taskService->getCommentsForTask($t['id']);
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
            <a href="deleted.php" class="btn btn-primary btn-sm">Deleted Tasks</a>
            <a href="overview.php" class="btn btn-primary btn-sm">All tasks</a>
        </div>
    <?php endif; ?>

    <!-- TABS NAVIGATION -->
    <ul class="nav nav-tabs mb-4" id="taskTabs" role="tablist">
        
        <?php if ($loggedRole !== 'Super Admin'): ?>
            <li class="nav-item">
                <a class="nav-link active" id="to-me-tab" data-toggle="tab" href="#to-me" role="tab">
                    Assigned To Me
                </a>
            </li>
        <?php endif; ?>

        <?php if (!empty($tasksAssignedByMe)): ?>
            <li class="nav-item">
                <a class="nav-link <?= ($loggedRole === 'Super Admin') ? 'active' : '' ?>" id="by-me-tab" data-toggle="tab" href="#by-me" role="tab">
                    Assigned By Me
                </a>
            </li>
        <?php endif; ?>
    </ul>

    <!-- TABS CONTENT -->
    <div class="tab-content" id="taskTabsContent">

        <!-- TAB 1: ASSIGNED TO ME -->
        <?php if ($loggedRole !== 'Super Admin'): ?>
            <div class="tab-pane fade show active" id="to-me" role="tabpanel">
                
                <?php if (empty($tasksAssignedToMe)): ?>
                    <div class="alert alert-info">No tasks assigned to you.</div>
                <?php else: ?>
                    <div class="card shadow-sm mb-5">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="assignedToMeTable"
                                    class="table table-bordered table-hover align-middle" style="width:100%">
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
                                                
                                                <td>
                                                    <button type="button"
                                                        class="btn btn-sm btn-info view-task"
                                                        data-title="<?= htmlspecialchars($task['title']) ?>"
                                                        data-desc="<?= htmlspecialchars($task['description']) ?>"
                                                        data-start="<?= date('d M Y', strtotime($task['start_date'])) ?>"
                                                        data-end="<?= date('d M Y', strtotime($task['end_date'])) ?>"
                                                        data-status="<?= htmlspecialchars($task['status']) ?>"
                                                        data-assigned="<?= htmlspecialchars($task['assigned_by_email']) ?>"
                                                        data-comments='<?= json_encode($taskComments[$task['id']]) ?>'>
                                                        View
                                                    </button>
                                                    <button type="button"
                                                        class="btn btn-sm btn-secondary comment-task"
                                                        data-task="<?= $task['id'] ?>">
                                                        Comment
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
        <?php endif; ?>

        <!-- TAB 2: ASSIGNED BY ME -->
        <?php if (!empty($tasksAssignedByMe)): ?>
            <div class="tab-pane fade <?= ($loggedRole === 'Super Admin') ? 'show active' : '' ?>" id="by-me" role="tabpanel">
                
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="assignedByMeTable"
                                class="table table-bordered table-hover align-middle" style="width:100%">
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
                                                <button type="button"
                                                    class="btn btn-sm btn-info view-task"
                                                    data-title="<?= htmlspecialchars($task['title']) ?>"
                                                    data-desc="<?= htmlspecialchars($task['description']) ?>"
                                                    data-start="<?= date('d M Y', strtotime($task['start_date'])) ?>"
                                                    data-end="<?= date('d M Y', strtotime($task['end_date'])) ?>"
                                                    data-status="<?= htmlspecialchars($task['status']) ?>"
                                                    data-assigned="<?= htmlspecialchars($task['assigned_by_email']) ?>"
                                                    data-comments='<?= json_encode($taskComments[$task['id']]) ?>'>
                                                    View
                                                </button>

                                                <button type="button"
                                                    class="btn btn-sm btn-secondary comment-task"
                                                    data-task="<?= $task['id'] ?>">
                                                    Comment
                                                </button>

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

            </div>
        <?php endif; ?>

    </div>

</div>

<!-- View Task Modal -->
<div class="modal fade" id="userTaskModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">

            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title">Task Details</h6>
                <button type="button" class="close text-white" data-dismiss="modal">
                    &times;
                </button>
            </div>

            <div class="modal-body">
                <p><strong>Title:</strong> <span id="uTitle"></span></p>
                <p><strong>Assigned By:</strong> <span id="uAssigned"></span></p>
                <p><strong>Description:</strong> <span id="uDesc"></span></p>
                <p><strong>Start:</strong> <span id="uStart"></span></p>
                <p><strong>End:</strong> <span id="uEnd"></span></p>
                <p>
                    <strong>Status:</strong>
                    <span id="uStatus" class="badge badge-secondary"></span>
                </p>
                <hr>
                <p><strong>Comments:</strong></p>
                <div id="uComments">
                    <small class="text-muted">No comments yet.</small>
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

<!-- Comment Modal -->
<div class="modal fade" id="commentModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">

            <form method="POST" action="add_comments.php">

                <div class="modal-header bg-secondary text-white">
                    <h6 class="modal-title">Add Comment</h6>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        &times;
                    </button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="task_id" id="commentTaskId">

                    <textarea name="comment"
                        class="form-control"
                        rows="4"
                        placeholder="Write your comment..."></textarea>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary btn-sm" data-dismiss="modal">
                        Cancel
                    </button>
                    <button class="btn btn-primary btn-sm">
                        Save Comment
                    </button>
                </div>

            </form>

        </div>
    </div>
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

        // View Task Handler
        $('.view-task').on('click', function() {
            const status = $(this).data('status');
            const comments = $(this).data('comments') || [];

            $('#uTitle').text($(this).data('title'));
            $('#uAssigned').text($(this).data('assigned'));
            $('#uDesc').text($(this).data('desc') || '-');
            $('#uStart').text($(this).data('start'));
            $('#uEnd').text($(this).data('end'));
            $('#uStatus').text(status);

            $('#uStatus')
                .removeClass('badge-success badge-warning badge-secondary badge-danger')
                .addClass(
                    status === 'Completed' ? 'badge-success' :
                    status === 'In Progress' ? 'badge-warning' :
                    'badge-secondary'
                );

            if (comments.length === 0) {
                $('#uComments').html('<small class="text-muted">No comments yet.</small>');
            } else {
                let html = '<ul class="list-group list-group-flush">';
                comments.forEach(c => {
                    html += `
                        <li class="list-group-item px-0">
                            <strong>${c.email}</strong><br>
                            ${c.comment}
                            <div class="small text-muted">
                                ${c.created_at}
                            </div>
                        </li>
                    `;
                });
                html += '</ul>';
                $('#uComments').html(html);
            }

            $('#userTaskModal').modal('show');
        });

        // Comment Task Handler
        $('.comment-task').on('click', function() {
            const taskId = $(this).data('task');
            $('#commentTaskId').val(taskId);
            $('#commentModal').modal('show');
        });

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