<?php
require_once '../../config/database.php';
//require_once '../../includes/authMiddleware.php';
require_once '../../includes/roleService.php';

// get db connection
$dbObj = new Database();
$db = $dbObj->getConnection();

// role service
$roleService = new RoleService($db);

// fetch all roles (active + deleted)
$roles = $roleService->getAllRoles(true);
?>
<!DOCTYPE html>
<html>

<head>
    <title>Roles List</title>

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

    <?php require_once '../../includes/header.php'; ?>

    <style>
        body {
            font-family: Arial;
            padding: 20px;
        }

        .btn {
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }

        .btn-add {
            background: #2ecc71;
            color: #fff;
        }

        .btn-edit {
            background: #3498db;
            color: #fff;
        }

        .btn-delete {
            background: #e74c3c;
            color: #fff;
        }

        .btn-restore {
            background: #f39c12;
            color: #fff;
        }

        .status-active {
            color: green;
            font-weight: bold;
        }

        .status-inactive {
            color: red;
            font-weight: bold;
        }

        .deleted {
            color: #999;
        }
    </style>
</head>

<body>

    <h2>Roles</h2>

    <a href="add.php" class="btn btn-add">+ Add Role</a>
    <a href="../users/list.php" class="btn btn-add">Go to Users</a>

    <br><br>

    <table id="rolesTable" class="display">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Slug</th>
                <th>Status</th>
                <th>Deleted</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>

            <?php if (!empty($roles)) { ?>
                <?php foreach ($roles as $role) { ?>
                    <tr class="<?php echo $role['deleted_at'] ? 'deleted' : ''; ?>">
                        <td><?php echo $role['id']; ?></td>
                        <td><?php echo htmlspecialchars($role['name']); ?></td>
                        <td><?php echo htmlspecialchars($role['slug']); ?></td>
                        <td>
                            <?php if ($role['status'] == 1) { ?>
                                <span class="status-active">Active</span>
                            <?php } else { ?>
                                <span class="status-inactive">Inactive</span>
                            <?php } ?>
                        </td>
                        <td>
                            <?php echo $role['deleted_at'] ? 'Yes' : 'No'; ?>
                        </td>
                        <td>
                            <?php if ($role['deleted_at']) { ?>
                                <a href="#"
                                    class="btn btn-restore"
                                    onclick="return confirmRestore('restore.php?id=<?php echo $role['id']; ?>')">
                                    Restore
                                </a>
                            <?php } else { ?>
                                <a class="btn btn-edit"
                                    href="edit.php?id=<?php echo $role['id']; ?>">
                                    Edit
                                </a>
                                <a href="#"
                                    class="btn btn-delete"
                                    onclick="return confirmDelete('delete.php?id=<?php echo $role['id']; ?>')">
                                    Delete
                                </a>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            <?php } ?>

        </tbody>
    </table>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            $('#rolesTable').DataTable();
        });
    </script>

    <script>
        // reusable delete alert
        function confirmDelete(url) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'This role will be deleted',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
            return false;
        }

        // reusable restore alert
        function confirmRestore(url) {
            Swal.fire({
                title: 'Restore role?',
                text: 'This role will be restored',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, restore',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
            return false;
        }
    </script>

    <?php require_once '../../includes/footer.php'; ?>

    <?php if (isset($_SESSION['swal'])) { ?>
        <script>
            Swal.fire({
                icon: '<?php echo $_SESSION['swal']['icon']; ?>',
                title: '<?php echo $_SESSION['swal']['title']; ?>',
                text: '<?php echo $_SESSION['swal']['text']; ?>'
            });
        </script>
    <?php unset($_SESSION['swal']);
    } ?>

</body>

</html>