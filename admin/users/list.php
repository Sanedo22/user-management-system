<?php
require_once '../../includes/repo/auth.php';
requireLogin();
requireRole(['Super Admin', 'Admin']);

$loggedInUser = $_SESSION['user'];

require_once '../../config/database.php';
require_once '../../includes/services/UserService.php';

// db connection
$dbObj = new Database();
$db = $dbObj->getConnection();

// user service
$userService = new UserService($db);

// get users (active + deleted)
$users = $userService->getAllUsers(true);

$title = 'Users';
require_once '../../includes/header.php';
?>

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Users</h1>

        <div>
            <a href="add.php" class="btn btn-primary btn-sm">+ Add User</a>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card shadow-sm">
        <div class="card-body">

            <div class="table-responsive">
                <table id="usersTable" class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Deleted</th>
                            <th style="width:180px;">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($users as $user) { ?>
                            <tr class="<?= $user['deleted_at'] ? 'table-danger' : '' ?>">
                                <td><?= $user['id']; ?></td>
                                <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td><?= htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge badge-info">
                                        <?= htmlspecialchars($user['role_name'] ?? '—'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        trim(($user['country_code'] ?? '') . ' ' . ($user['phone_number'] ?? ''))
                                    ); ?>
                                </td>
                                <td>
                                    <?php if ($userService->isUserOnline($user['id'])): ?>
                                        <span class="badge badge-success">Online</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Offline</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $user['deleted_at'] ? 'Yes' : 'No'; ?></td>
                                <td>
                                    <?php if ($user['deleted_at']) { ?>
                                        <button class="btn btn-success btn-sm"
                                            onclick="confirmRestore(<?= $user['id'] ?>)">
                                            Restore
                                        </button>
                                    <?php } else { ?>

                                        <?php if ($loggedInUser['id'] == $user['id']) { ?>

                                            <span class="badge badge-secondary"
                                                title="You cannot edit or delete your own account">
                                                You
                                            </span>

                                        <?php } else { ?>

                                            <?php if (
                                                $loggedInUser['role_name'] === 'Admin' &&
                                                $user['role_name'] === 'Super Admin'
                                            ) { ?>

                                                <button type="button"
                                                    class="btn btn-info btn-sm"
                                                    onclick="Swal.fire({
                                                                    icon: 'error',
                                                                    title: 'Action not allowed',
                                                                    text: 'You are not allowed to edit the Super Admin account.',
                                                                    confirmButtonText: 'OK'
                                                                })">
                                                    Edit
                                                </button>

                                            <?php } else { ?>

                                                <a class="btn btn-info btn-sm"
                                                    href="edit.php?id=<?= $user['id']; ?>">
                                                    Edit
                                                </a>

                                            <?php } ?>


                                            <?php
                                            $canDelete = false;

                                            if ($loggedInUser['role_name'] === 'Super Admin') {
                                                if ($loggedInUser['id'] != $user['id'] && $user['role_name'] !== 'Super Admin') {
                                                    $canDelete = true;
                                                }
                                            }

                                            if ($loggedInUser['role_name'] === 'Admin') {
                                                if (!in_array($user['role_name'], ['Super Admin', 'Admin'])) {
                                                    $canDelete = true;
                                                }
                                            }
                                            ?>

                                            <?php if ($canDelete): ?>
                                                <button class="btn btn-danger btn-sm"
                                                    onclick="confirmDelete(<?= $user['id']; ?>)">
                                                    Delete
                                                </button>
                                            <?php endif; ?>

                                        <?php } ?>

                                    <?php } ?>

                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<!-- DataTables core -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<!-- DataTables Bootstrap 4 integration -->
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

<script>
    $(document).ready(function() {
        $('#usersTable').DataTable();
    });

    function confirmDelete(userId) {
        Swal.fire({
            title: 'Are you sure?',
            text: 'User will be soft deleted',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'delete.php?id=' + userId;
            }
        });
    }

    function confirmRestore(userId) {
        Swal.fire({
            title: 'Restore this user?',
            text: 'The user account will be reactivated',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, restore'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'restore.php?id=' + userId;
            }
        });

        // function cannotEditSuperAdmin() {
        //     Swal.fire({
        //         icon: 'error',
        //         title: 'Action not allowed',
        //         text: 'You are not allowed to edit the Super Admin account.',
        //         confirmButtonText: 'OK'
        //     });
        // }

    }
</script>

<?php require_once '../../includes/footer.php'; ?>