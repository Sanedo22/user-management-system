<?php
require_once '../../includes/repo/auth.php';
requireLogin();
requireRole(['Super Admin']);

require_once '../../config/database.php';
require_once '../../includes/services/RoleService.php';

// get db connection
$dbObj = new Database();
$db = $dbObj->getConnection();

// role service
$roleService = new RoleService($db);

// fetch all roles (active + deleted)
$roles = $roleService->getAllRoles(true);

$title = 'Roles';
require_once __DIR__ . '/../../includes/layouts/admin/header.php';
?>

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Roles</h1>

        <div>
            <a href="add.php" class="btn btn-primary btn-sm">+ Add Role</a>
        </div>
    </div>

    <!-- Roles Table -->
    <div class="card shadow-sm">
        <div class="card-body">

            <div class="table-responsive">
                <table id="rolesTable"
                       class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Status</th>
                            <th>Deleted</th>
                            <th style="width:160px;">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!empty($roles)) { ?>
                            <?php foreach ($roles as $role) { ?>
                                <tr class="<?= $role['deleted_at'] ? 'table-danger' : '' ?>">
                                    <td><?= $role['id']; ?></td>

                                    <td>
                                        <span class="badge badge-info">
                                            <?= htmlspecialchars($role['name']); ?>
                                        </span>
                                    </td>

                                    <td><?= htmlspecialchars($role['slug']); ?></td>

                                    <td>
                                        <?php if ($role['status'] == 1) { ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php } else { ?>
                                            <span class="badge badge-secondary">Inactive</span>
                                        <?php } ?>
                                    </td>

                                    <td><?= $role['deleted_at'] ? 'Yes' : 'No'; ?></td>

                                    <td>
                                        <?php if ($role['deleted_at']) { ?>

                                            <button class="btn btn-success btn-sm"
                                                    onclick="confirmRestore(<?= $role['id']; ?>)">
                                                Restore
                                            </button>

                                        <?php } else { ?>

                                            <a href="edit.php?id=<?= $role['id']; ?>"
                                               class="btn btn-info btn-sm">
                                                Edit
                                            </a>

                                            <button class="btn btn-danger btn-sm"
                                                    onclick="confirmDelete(<?= $role['id']; ?>)">
                                                Delete
                                            </button>

                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

<script>
$(document).ready(function () {
    $('#rolesTable').DataTable();
});

// delete confirmation
function confirmDelete(roleId) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'This role will be deleted',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete'
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
        confirmButtonText: 'Yes, restore'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'restore.php?id=' + roleId;
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/layouts/admin/footer.php';
 ?>
