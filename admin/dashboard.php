<?php require_once '../includes/header.php'; ?>

<div class="dashboard">

<h2>Super Admin Dashboard</h2>

<ul>
    <li><a href="roles/list.php">Manage Roles</a></li>
    <li><a href="users/list.php">Manage Users</a></li>
    <li><a href="logout.php">Logout</a></li>
</ul>

<h3>Two-Factor Authentication</h3>

<div class="twofa-box">

<?php if ($_SESSION['user']['twofa_enabled'] ?? false): ?>
    <p class="twofa-enabled">Two-Factor Authentication is ENABLED</p>

    <form method="post" action="twofa/disable.php">
        <label>Confirm Password to Disable 2FA</label><br>
        <input type="password" name="password" required>
        <br>
        <button type="submit">Disable 2FA</button>
    </form>

<?php else: ?>
    <p class="twofa-disabled">Two-Factor Authentication is DISABLED</p>
    <a href="twofa/setup.php" class="enable-2fa">Enable 2FA</a>
<?php endif; ?>

</div>
</div>
