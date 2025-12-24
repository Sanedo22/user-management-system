<?php
require_once '../includes/repo/auth.php';
requireLogin();


require_once '../config/database.php';
$title = 'Dashboard';
require_once '../includes/header.php';

$db = (new Database())->getConnection();

// total users count
$stmt = $db->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL");
$totalUsers = (int)$stmt->fetchColumn();

// total roles count
$stmt = $db->query("SELECT COUNT(*) FROM roles");
$totalRoles = (int)$stmt->fetchColumn();

?>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Admin Dashboard</h1>

    <!-- MAIN ACTIONS -->
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


        <?php if ($_SESSION['user']['role_name'] === 'Super Admin'): ?>
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

        <?php endif; ?>

    </div>

    <!-- SECURITY STATUS -->
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title">Security</h5>

            <?php if ($_SESSION['user']['twofa_enabled']): ?>
                <div class="alert alert-success mb-3">
                    Two-Factor Authentication is enabled for your account.
                </div>
            <?php else: ?>
                <div class="alert alert-warning mb-3">
                    Two-Factor Authentication is not enabled.
                </div>
                <a href="twofa/setup.php" class="btn btn-warning btn-sm">
                    Enable 2FA
                </a>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php require_once '../includes/footer.php'; ?>