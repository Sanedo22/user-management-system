<?php
require_once '../includes/repo/auth.php';
requireLogin();
requireRole(['Admin', 'Manager', 'Super Admin']);


require_once '../config/database.php';
$title = 'Dashboard';
require_once '../includes/header.php';

$db = (new Database())->getConnection();

// total users count
$stmt = $db->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL");
$totalUsers = (int)$stmt->fetchColumn();

//online users only count
$stmt = $db->query("
    SELECT COUNT(DISTINCT user_id)
    FROM user_sessions
    WHERE is_active = 1
    AND last_activity >= (NOW() - INTERVAL 30 MINUTE)
");
$onlineUsers = (int)$stmt->fetchColumn();


// total roles count
$stmt = $db->query("SELECT COUNT(*) FROM roles");
$totalRoles = (int)$stmt->fetchColumn();

//deleted users count
$stmt = $db->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NOT NULL");
$deletedUsers = (int)$stmt->fetchColumn();


?>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">User-Management-System</h1>

    <?php if (in_array($_SESSION['user']['role_name'], ['Super Admin', 'Admin'])): ?>
        <div class="mb-4">
            <a href="../admin/users/add.php" class="btn btn-primary btn-sm me-2">
                + Add User
            </a>
        <?php endif; ?>

        <?php if ($_SESSION['user']['role_name'] === 'Super Admin'): ?>
            <a href="../admin/roles/add.php" class="btn btn-secondary btn-sm">
                + Add Role
            </a>
        <?php endif; ?>

        <?php if (in_array($_SESSION['user']['role_name'], ['Super Admin', 'Admin', 'Manager'])): ?>
            <a href="../admin/tasks/add.php" class="btn btn-primary btn-sm">+ Add Tasks </a>
        <?php endif; ?>
        </div>

        <!-- MAIN ACTIONS -->
        <?php if (in_array($_SESSION['user']['role_name'], ['Super Admin', 'Admin'])): ?>
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm border-left-primary">
                        <div class="card-body">
                            <h6 class="text-uppercase text-primary fw-bold mb-1">
                                Total Users
                            </h6>
                            <h2 class="mb-0">
                                <?= $totalUsers ?>
                            </h2>
                            <small class="text-muted">
                                Active registered users in the system
                            </small>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (in_array($_SESSION['user']['role_name'], ['Super Admin', 'Admin'])): ?>
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm border-left-success">
                        <div class="card-body">
                            <h6 class="text-uppercase text-success fw-bold mb-1">
                                Users Online
                            </h6>
                            <h2 class="mb-0"><?= $onlineUsers ?></h2>
                            <small class="text-muted">
                                Active in last 30 minutes
                            </small>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (in_array($_SESSION['user']['role_name'], ['Super Admin', 'Admin'])): ?>
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm border-left-danger">
                        <div class="card-body">
                            <h6 class="text-uppercase text-danger fw-bold mb-1">
                                Deleted Users
                            </h6>
                            <h2 class="mb-0"><?= $deletedUsers ?></h2>
                            <small class="text-muted">
                                Soft-deleted accounts
                            </small>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($_SESSION['user']['role_name'] === 'Super Admin'): ?>
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm border-left-secondary">
                        <div class="card-body">
                            <h6 class="text-uppercase text-secondary fw-bold mb-1">
                                Total Roles
                            </h6>
                            <h2 class="mb-0">
                                <?= $totalRoles ?>
                            </h2>
                            <small class="text-muted">
                                Roles defined for access control
                            </small>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            </div>

</div>

<?php require_once '../includes/footer.php'; ?>