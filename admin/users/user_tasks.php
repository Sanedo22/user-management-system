<?php
require_once '../../includes/repo/auth.php';
requireLogin();
// requireRole(['User']);

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once 'user-header.php';
require_once '../../includes/services/TaskService.php';
$db = (new Database())->getConnection();
$userId = $_SESSION['user']['id'];
$taskService = new TaskService($db);


$tasksAssignedToMe = $taskService->getTasksForUser($userId);

$taskComments = [];

foreach ($tasksAssignedToMe as $t) {
    $taskComments[$t['id']] = $taskService->getCommentsForTask($t['id']);
}


$errors = [];
$success = '';

// fetch user
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<div class="container-fluid py-4">

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <!-- MY TASKS -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0">My Tasks</h6>
        </div>

        <div class="card-body">

            <?php if (empty($tasksAssignedToMe)): ?>
                <div class="alert alert-info mb-0">
                    No tasks assigned to you yet.
                </div>
            <?php else: ?>

                <div class="table-responsive">
                    <table id="userTasksTable"
                        class="table table-bordered table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Task</th>
                                <th>Assigned By</th>
                                <th>Start</th>
                                <th>End</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php
                            $today = date('Y-m-d');
                            foreach ($tasksAssignedToMe as $task):

                                $isOverdue = (
                                    $task['status'] !== 'Completed' &&
                                    $today > $task['end_date']
                                );
                            ?>

                                <tr class="<?= $isOverdue ? 'table-danger' : '' ?>">
                                    <td>
                                        <strong><?= htmlspecialchars($task['title']) ?></strong>

                                        <?php if (!empty($task['description'])): ?>
                                            <div class="small text-muted">
                                                <?= nl2br(htmlspecialchars($task['description'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($task['assigned_by_email']) ?>
                                    </td>


                                    <td><?= date('d M Y', strtotime($task['start_date'])) ?></td>
                                    <td><?= date('d M Y', strtotime($task['end_date'])) ?></td>

                                    <td>
                                        <form method="POST"
                                            action="../tasks/update_status.php">
                                            <input type="hidden"
                                                name="task_id"
                                                value="<?= $task['id'] ?>">

                                            <select name="status"
                                                class="form-control form-control-sm"
                                                onchange="this.form.submit()">
                                                <?php foreach (['Pending', 'In Progress', 'Completed'] as $s): ?>
                                                    <option value="<?= $s ?>"
                                                        <?= $task['status'] === $s ? 'selected' : '' ?>>
                                                        <?= $s ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>

                                        <?php if ($isOverdue): ?>
                                            <span class="badge badge-danger mt-1">
                                                Overdue
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <button
                                            type="button"
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

            <?php endif; ?>

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

        <div class="modal fade" id="commentModal" tabindex="-1">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">

                    <form method="POST" action="../tasks/add_comments.php">

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


    </div>

</div>

<?php require_once 'user_footer.php'; ?>


<script>
    function confirmDisable2FA() {
        Swal.fire({
            title: 'Disable Two-Factor Authentication?',
            input: 'password',
            inputPlaceholder: 'Confirm your password',
            inputAttributes: {
                autocapitalize: 'off'
            },
            showCancelButton: true,
            confirmButtonText: 'Disable',
            confirmButtonColor: '#f6c23e',
            showLoaderOnConfirm: true,
            preConfirm: (password) => {
                if (!password) {
                    Swal.showValidationMessage('Password is required');
                    return false;
                }
                document.getElementById('disable2faPassword').value = password;
                document.getElementById('disable2faForm').submit();
            }
        });
    }

    //success message
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(a => {
            a.style.transition = 'opacity .3s';
            a.style.opacity = 0;
            setTimeout(() => a.remove(), 150);
        });
    }, 1000);
</script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

<script>
    $(function() {
        if ($('#userTasksTable').length) {
            $('#userTasksTable').DataTable({
                pageLength: 5,
                lengthChange: false,
                order: [
                    [2, 'asc']
                ]
            });
        }
    });
</script>

<script>
    $(function() {

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
                .removeClass('badge-success badge-warning badge-secondary')
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


    });

    $(function() {
        $('.comment-task').on('click', function() {
            const taskId = $(this).data('task');
            $('#commentTaskId').val(taskId);
            $('#commentModal').modal('show');
        });
    });
</script>



</body>

</html>