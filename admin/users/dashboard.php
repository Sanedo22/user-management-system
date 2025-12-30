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

$totalTasks = 0;
$completedTasks = 0;
$inProgressTasks = 0;
$pendingTasks = 0;

foreach ($tasksAssignedToMe as $task) {
    $totalTasks++;

    switch ($task['status']) {
        case 'Completed':
            $completedTasks++;
            break;
        case 'In Progress':
            $inProgressTasks++;
            break;
        case 'Pending':
            $pendingTasks++;
            break;
    }
}

$today = date('Y-m-d');

$todaysTasks = array_filter($tasksAssignedToMe, function ($task) use ($today) {
    return $task['start_date'] === $today;
});



$errors = [];
$success = '';

// fetch user
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<div class="container-fluid py-4">

    <!-- PAGE TITLE -->
    <div class="mb-4">
        <h3 class="text-gray-800">My Dashboard</h3>
        <small class="text-muted">Welcome back, <?= htmlspecialchars($user['first_name']) ?></small>
    </div>

    <div class="row mb-4">

        <!-- TOTAL TASKS -->
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-left-primary h-100">
                <div class="card-body">
                    <h6 class="text-uppercase text-primary font-weight-bold mb-1">
                        Total Tasks
                    </h6>
                    <h3 class="mb-0"><?= $totalTasks ?></h3>
                    <small class="text-muted">Assigned to you</small>
                </div>
            </div>
        </div>

        <!-- COMPLETED -->
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-left-success h-100">
                <div class="card-body">
                    <h6 class="text-uppercase text-success font-weight-bold mb-1">
                        Completed
                    </h6>
                    <h3 class="mb-0"><?= $completedTasks ?></h3>
                    <small class="text-muted">Finished tasks</small>
                </div>
            </div>
        </div>

        <!-- IN PROGRESS -->
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-left-warning h-100">
                <div class="card-body">
                    <h6 class="text-uppercase text-warning font-weight-bold mb-1">
                        In Progress
                    </h6>
                    <h3 class="mb-0"><?= $inProgressTasks ?></h3>
                    <small class="text-muted">Ongoing tasks</small>
                </div>
            </div>
        </div>

        <!-- PENDING -->
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-left-secondary h-100">
                <div class="card-body">
                    <h6 class="text-uppercase text-secondary font-weight-bold mb-1">
                        Pending
                    </h6>
                    <h3 class="mb-0"><?= $pendingTasks ?></h3>
                    <small class="text-muted">Not started</small>
                </div>
            </div>
        </div>

    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0">Today's Tasks</h6>
        </div>

        <div class="card-body">

            <?php if (empty($todaysTasks)): ?>
                <div class="alert alert-info mb-0">
                    No tasks scheduled for today.
                </div>
            <?php else: ?>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Task</th>
                                <th>Assigned By</th>
                                <th>Start</th>
                                <th>End</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($todaysTasks as $task):

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
                                            <span class="badge badge-danger mt-1">Overdue</span>
                                        <?php endif; ?>
                                    </td>

                                    </td>
                                </tr>

                            <?php endforeach; ?>
                        </tbody>

                    </table>
                </div>

            <?php endif; ?>

        </div>
    </div>





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





    </body>

    </html>