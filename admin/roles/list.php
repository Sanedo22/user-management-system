<?php
require_once '../../includes/auth.php';
requireLogin();
requireRole(['Super Admin']);

require_once '../../config/database.php';
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
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
</head>

<body>
<div class="container">
<?php require_once '../../includes/header.php'; ?>

<div class="page-header">
    <h2>Roles</h2>
    <a href="add.php" class="btn btn-add">+ Add Role</a>
    <a href="../users/list.php" class="btn btn-add">Go to Users</a>
    <a href="../dashboard.php" class="btn btn-add">Go to Dashboard</a>
    <br><br>
</div>

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
                    <td><?php echo $role['deleted_at'] ? 'Yes' : 'No'; ?></td>
                    <td>
                        <?php if ($role['deleted_at']) { ?>

                            <a href="javascript:void(0)"
                               class="btn btn-restore"
                               onclick="confirmRestore(<?php echo $role['id']; ?>)">
                                Restore
                            </a>

                        <?php } else { ?>

                            <a class="btn btn-edit"
                               href="edit.php?id=<?php echo $role['id']; ?>">
                                Edit
                            </a>

                            <a href="javascript:void(0)"
                               class="btn btn-delete"
                               onclick="confirmDelete(<?php echo $role['id']; ?>)">
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

<script>
$(document).ready(function() {
    $('#rolesTable').DataTable();
});

// delete confirmation
function confirmDelete(roleId) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'This role will be deleted',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'delete.php?id=' + roleId;
        }
    });
}

// restore confirmation
function confirmRestore(roleId) {
    Swal.fire({
        title: 'Restore role?',
        text: 'This role will be restored',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, restore',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'restore.php?id=' + roleId;
        }
    });
}
</script>

<?php require_once '../../includes/footer.php'; ?>
</div>
</body>

</html>
