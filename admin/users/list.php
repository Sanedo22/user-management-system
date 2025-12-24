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
?>

<!DOCTYPE html>
<html>

<head>
    <title>Users List</title>

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
</head>

<body>
    <div class="container">
        <?php require_once '../../includes/header.php'; ?>

        <div class="page-header">
            <h2>Users</h2>

            <a href="add.php" class="btn btn-add">+ Add User</a>
            <?php if ($_SESSION['user']['role_name'] === 'Super Admin'): ?>
                <a href="../roles/list.php" class="btn btn-add">Go to Roles</a>
            <?php endif; ?>
            <a href="../dashboard.php" class="btn btn-add">Go to Dashboard</a>
            <br><br>
        </div>

        <table id="usersTable" class="display">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Deleted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>

                <?php foreach ($users as $user) { ?>
                    <tr class="<?php echo $user['deleted_at'] ? 'deleted' : ''; ?>">
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['role_name'] ?? '—'); ?></td>
                        <td>
                            <?php
                            echo htmlspecialchars(
                                trim(($user['country_code'] ?? '') . ' ' . ($user['phone_number'] ?? ''))
                            );
                            ?>
                        </td>
                        <td>
                            <?php if ($userService->isUserOnline($user['id'])): ?>
                                <span class="status-active">Online</span>
                            <?php else: ?>
                                <span class="status-inactive">Offline</span>
                            <?php endif; ?>

                        </td>
                        <td><?php echo $user['deleted_at'] ? 'Yes' : 'No'; ?></td>
                        <td>
                            <?php if ($user['deleted_at']) { ?>

                                <a class="btn btn-restore"
                                    href="javascript:void(0)"
                                    onclick="confirmRestore(<?= $user['id'] ?>)">
                                    Restore
                                </a>


                            <?php } else { ?>

                                <a class="btn btn-edit"
                                    href="edit.php?id=<?php echo $user['id']; ?>">
                                    Edit
                                </a>

                                <?php
                                $canDelete = false;

                                if ($loggedInUser['role_name'] === 'Super Admin') {
                                    if ($loggedInUser['id'] != $user['id'] && $user['role_name'] !== 'Super Admin') {
                                        $canDelete = true;
                                    }
                                }

                                if ($loggedInUser['role_name'] === 'Admin') {
                                    if (in_array($user['role_name'], ['Manager', 'User'])) {
                                        $canDelete = true;
                                    }
                                }
                                ?>

                                <?php if ($canDelete): ?>
                                    <a class="btn btn-delete"
                                        href="javascript:void(0)"
                                        onclick="confirmDelete(<?php echo $user['id']; ?>)">
                                        Delete
                                    </a>
                                <?php endif; ?>

                            <?php } ?>
                        </td>

                    </tr>
                <?php } ?>

            </tbody>
        </table>

        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

        <!-- DataTables JS -->
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

        <script>
            $(document).ready(function() {
                $('#usersTable').DataTable();
            });

            function confirmDelete(userId) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This action cannot be undone',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
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
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, restore'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'restore.php?id=' + userId;
                    }
                });
            }
        </script>

        </script>

        <?php include "../../includes/footer.php"; ?>
    </div>
</body>

</html>