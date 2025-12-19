<?php
    require_once'../includes/auth.php';
    require_once'../includes/AuthService.php';

    requireLogin();
    requireRole(['Super Admin', 'Admin']);

    require_once'../includes/header.php';
?>

<h2> Super Admin Dashboard </h2>

<ul>
    <li><a href="roles/list.php">Manage Roles</a></li>
    <li><a href="users/list.php">manage Users</a></li>
    <li><a href="logout.php">Logout</a></li>
</ul>
