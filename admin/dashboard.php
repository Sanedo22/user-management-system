<?php 
require_once '../includes/auth.php';
requireLogin();
requireRole(['Admin', 'Super Admin']);
require_once '../config/database.php';

$db = (new Database())->getConnection();

$sql = "SELECT twofa_enabled FROM users WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$_SESSION['user']['id']]);
$twofaEnabled = (int)$stmt->fetchColumn();
?>

<link rel="stylesheet" href="../assets/css/dashboard.css">

<div class="container">

    <h2 class="dashboard-title">Admin Dashboard</h2>

    <!-- MAIN ACTION CARDS -->
    <div class="dashboard-cards">
        <a href="roles/list.php" class="dash-card">
            <span class="dash-title">Roles</span>
            <span class="dash-desc">Create & manage roles</span>
        </a>

        <a href="users/list.php" class="dash-card">
            <span class="dash-title">Users</span>
            <span class="dash-desc">Manage system users</span>
        </a>

        <a href="logout.php" class="dash-card danger">
            <span class="dash-title">Logout</span>
            <span class="dash-desc">End current session</span>
        </a>
    </div>

    <!-- 2FA SECTION -->
    <div class="dashboard-section">
        <h3>Security (Two-Factor Authentication)</h3>

        <div class="twofa-box">

            <?php if ($twofaEnabled === 1): ?>
                <p class="twofa-enabled">Two-Factor Authentication is ENABLED</p>

                <form method="post" action="twofa/disable.php">
                    <label>Confirm Password to Disable</label>
                    <input type="password" name="password" required>
                    <button type="submit" class="btn danger">Disable 2FA</button>
                </form>

            <?php else: ?>
                <p class="twofa-disabled">Two-Factor Authentication is DISABLED</p>
                <a href="twofa/setup.php" class="btn primary">Enable 2FA</a>
            <?php endif; ?>

        </div>
    </div>

</div>