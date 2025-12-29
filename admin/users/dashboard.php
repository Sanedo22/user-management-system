<?php
require_once '../../includes/repo/auth.php';
requireLogin();
// requireRole(['User']);

require_once '../../config/database.php';
require_once '../../includes/services/TaskService.php';
$db = (new Database())->getConnection();
$userId = $_SESSION['user']['id'];
$taskService = new TaskService($db);


$tasksAssignedToMe = $taskService->getTasksForUser($userId);

$errors = [];
$success = '';

// fetch user
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$baseUrl = '/user_management-system/user-management-system';

$profileImage = !empty($user['profile_img'])
    ? $baseUrl . '../admin/uploads/profiles/' . $user['profile_img']
    : $baseUrl . '../admin/uploads/profiles/default.png';

/* -----------------------------
   UPDATE PROFILE
------------------------------*/
if (isset($_POST['upload_image'])) {

    $profileImg = $user['profile_img'];

    if (!empty($_FILES['profile_img']['name'])) {
        $ext = pathinfo($_FILES['profile_img']['name'], PATHINFO_EXTENSION);
        $fileName = 'user_' . $userId . '.' . $ext;
        $uploadPath = '../../admin/uploads/profiles/' . $fileName;
        move_uploaded_file($_FILES['profile_img']['tmp_name'], $uploadPath);
        $profileImg = $fileName;
    }

    $sql = "UPDATE users SET profile_img=? WHERE id=?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$profileImg, $userId]);
    $success = 'Profile image updated successfully';
}

/* -----------------------------
   CHANGE PASSWORD
------------------------------*/
if (isset($_POST['change_password'])) {

    $current = $_POST['current_password'];
    $new     = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if ($new !== $confirm) {
        $errors[] = 'Passwords do not match';
    } elseif (!password_verify($current, $user['password'])) {
        $errors[] = 'Current password incorrect';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password=? WHERE id=?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$hash, $userId]);
        $success = 'Password changed successfully';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <title>User Dashboard</title>

    <link rel="stylesheet"
        href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <link href="<?= BASE_URL ?>/assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/sb-admin-2.min.css" rel="stylesheet">

    <!-- Bootstrap (same version used in admin) -->
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-light">

    <div class="container-fluid py-4">

        <!-- PAGE TITLE -->
        <div class="mb-4">
            <h3 class="text-gray-800">My Dashboard</h3>
            <small class="text-muted">Welcome back, <?= htmlspecialchars($user['first_name']) ?></small>
        </div>

        <!-- TOP ROW -->
        <div class="row">

            <!-- PROFILE CARD -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <img src="<?= htmlspecialchars($profileImage) ?>"
                            class="rounded-circle mb-3"
                            style="width:100px;height:100px;object-fit:cover;">
                        <h6 class="mb-0">
                            <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                        </h6>
                        <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                    </div>
                </div>
            </div>

            <!-- SECURITY CARD -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-primary mb-3">Security</h6>

                        <?php if ($_SESSION['user']['twofa_enabled']): ?>
                            <div class="alert alert-success py-2">
                                2FA is enabled
                            </div>
                            <button class="btn btn-warning btn-sm"
                                onclick="confirmDisable2FA()">
                                Disable 2FA
                            </button>
                        <?php else: ?>
                            <div class="alert alert-warning py-2">
                                2FA is not enabled
                            </div>
                            <a href="<?= BASE_URL ?>/admin/twofa/setup.php"
                                class="btn btn-primary btn-sm">
                                Enable 2FA
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- CHANGE PASSWORD -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-primary mb-3">Change Password</h6>

                        <form method="post">
                            <input type="password"
                                name="current_password"
                                class="form-control mb-2"
                                placeholder="Current password"
                                required>

                            <input type="password"
                                name="new_password"
                                class="form-control mb-2"
                                placeholder="New password"
                                required>

                            <input type="password"
                                name="confirm_password"
                                class="form-control mb-2"
                                placeholder="Confirm password"
                                required>

                            <button type="submit"
                                name="change_password"
                                class="btn btn-sm btn-warning">
                                Update Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>

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

                                            <button
                                                type="button"
                                                class="btn btn-link btn-sm p-0 ml-1 view-task"
                                                data-title="<?= htmlspecialchars($task['title']) ?>"
                                                data-desc="<?= htmlspecialchars($task['description']) ?>"
                                                data-start="<?= date('d M Y', strtotime($task['start_date'])) ?>"
                                                data-end="<?= date('d M Y', strtotime($task['end_date'])) ?>"
                                                data-status="<?= htmlspecialchars($task['status']) ?>"
                                                data-assigned="<?= htmlspecialchars($task['assigned_by_email']) ?>">
                                                (view)
                                            </button>

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
                                                    onchange="this.form.submit()"
                                                    <?= $task['status'] === 'Completed' ? 'disabled' : '' ?>>
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
                        </div>

                        <div class="modal-footer">
                            <button class="btn btn-secondary btn-sm" data-dismiss="modal">
                                Close
                            </button>
                        </div>

                    </div>
                </div>
            </div>

        </div>

        <!-- LOGOUT -->
        <a href="../logout.php" class="btn btn-danger btn-sm">
            Logout
        </a>

    </div>


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

                $('#userTaskModal').modal('show');
            });

        });
    </script>



</body>

</html>