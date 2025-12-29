<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();

    require_once __DIR__ . '/../config/constants.php';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= $pagetitle ?? ($title ?? 'Admin Panel') ?></title>

    <!-- SB Admin 2 CSS -->
    <link href="<?= BASE_URL ?>/assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/sb-admin-2.min.css" rel="stylesheet">

    <!-- Datatables bootstrap4 -->
    <link rel="stylesheet"
        href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">

    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body id="page-top">

    <div id="wrapper">

        <!-- SIDEBAR -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?= BASE_URL ?>/admin/dashboard.php">
                <div class="sidebar-brand-text mx-3">UMS</div>
            </a>

            <hr class="sidebar-divider my-0">

            <?php if (in_array($_SESSION['user']['role_name'], ['Super Admin', 'Admin'])): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/admin/dashboard.php">
                        <i class="fas fa-fw fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (in_array($_SESSION['user']['role_name'], ['Super Admin', 'Admin'])): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/admin/users/list.php">
                        <i class="fas fa-fw fa-users"></i>
                        <span>Users</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($_SESSION['user']['role_name'] === 'Super Admin'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/admin/roles/list.php">
                        <i class="fas fa-fw fa-user-shield"></i>
                        <span>Roles</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (in_array($_SESSION['user']['role_name'], ['Manager', 'Super Admin', 'Admin'])): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/admin/tasks/list.php">
                        <i class="fas fa-tasks"></i>
                        <span>Tasks</span>
                    </a>
                </li>
            <?php endif; ?>

            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>/admin/profile.php">
                    <i class="fas fa-user"></i>
                    <span>My Profile</span>
                </a>
            </li>
            <hr class="sidebar-divider">

            <li class="nav-item">
                <a class="nav-link text-danger" href="<?= BASE_URL ?>/admin/logout.php">
                    <i class="fas fa-fw fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>

        </ul>
        <!-- END SIDEBAR -->

        <!-- CONTENT WRAPPER -->
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">

                <!-- TOPBAR -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                    <!-- <button id="sidebarToggleTop" class="btn btn-link rounded-circle mr-3" ">
                        <i class=" fas fa-bars"></i>
                    </button> -->

                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                                <span class="mr-2 text-gray-600 small">
                                    Welcome,
                                    <?= $_SESSION['user']['email'] ?>
                                </span>
                            </a>
                        </li>
                    </ul>
                </nav>