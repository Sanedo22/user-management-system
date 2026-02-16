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

//deleted users count
$stmt = $db->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NOT NULL");
$deletedUsers = (int)$stmt->fetchColumn();

// total roles count
$stmt = $db->query("SELECT COUNT(*) FROM roles");
$totalRoles = (int)$stmt->fetchColumn();

//tasks count
$stmt = $db->query("
    SELECT status, COUNT(*) as total
    FROM tasks
    WHERE deleted_at IS NULL
    GROUP BY status
");

$taskStats = [
    'Pending' => 0,
    'In Progress' => 0,
    'Completed' => 0
];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $taskStats[$row['status']] = (int)$row['total'];
}

// Tasks assigned today
$stmt = $db->prepare("
    SELECT 
            t.*,
            u1.email AS assigned_to_email,
            u2.email AS assigned_by_email
        FROM tasks t
        JOIN users u1 ON u1.id = t.assigned_to
        JOIN users u2 ON u2.id = t.assigned_by
        WHERE DATE(t.start_date) = CURDATE()
          AND t.deleted_at IS NULL
        ORDER BY t.start_date ASC
    ");
$stmt->execute();
$todayTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);




?>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">User-Management-System</h1>

    <!-- MAIN ACTIONS -->
    <?php if (in_array($_SESSION['user']['role_name'], ['Super Admin', 'Admin'])): ?>
        <div class="row">
            <div class="col-xl-4 col-md-6 mb-4">
                <a href="<?= BASE_URL ?>/admin/users/list.php"
                    class="text-decoration-none">

                    <div class="card shadow-sm border-left-primary h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">

                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Users Overview
                                    </div>

                                    <div class="h5 mb-1 font-weight-bold text-gray-800">
                                        <?= $totalUsers ?> Users
                                    </div>

                                    <div class="small text-muted">
                                        <span class="badge badge-success"><?= $onlineUsers ?> Online</span> ·
                                        <span class="badge badge-danger"><?= $deletedUsers ?> Deleted</span>
                                    </div>

                                    <div class="mt-2 small text-primary">
                                        View users →
                                    </div>
                                </div>

                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>

                            </div>
                        </div>
                    </div>

                </a>
            </div>

        <?php endif; ?>

        <?php if ($_SESSION['user']['role_name'] === 'Super Admin'): ?>
            <div class="col-xl-4 col-md-6 mb-4">

                <a href="<?= BASE_URL ?>/admin/roles/list.php"
                    class="text-decoration-none">

                    <div class="card shadow-sm border-left-secondary h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">

                                <!-- TEXT -->
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                        Roles & Permissions
                                    </div>

                                    <div class="h5 mb-1 font-weight-bold text-gray-800">
                                        <?= $totalRoles ?> Roles
                                    </div>

                                    <div class="small text-muted">
                                        Access control & RBAC rules
                                    </div>

                                    <div class="mt-2 small text-secondary">
                                        Manage roles →
                                    </div>
                                </div>

                                <!-- ICON -->
                                <div class="col-auto">
                                    <i class="fas fa-user-shield fa-2x text-gray-300"></i>
                                </div>

                            </div>
                        </div>
                    </div>

                </a>

            </div>
        <?php endif; ?>

        <?php if (in_array($_SESSION['user']['role_name'], ['Super Admin', 'Admin', 'Manager'])): ?>
            <div class="col-xl-4 col-md-6 mb-4">

                <a href="<?= BASE_URL ?>/admin/tasks/list.php"
                    class="text-decoration-none">

                    <div class="card shadow-sm border-left-warning h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">

                                <!-- TEXT -->
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Tasks Overview
                                    </div>

                                    <div class="mb-2 font-weight-bold text-gray-800">
                                        Total Tasks:
                                        <?= array_sum($taskStats) ?>
                                    </div>

                                    <!-- STATUS COUNTS -->
                                    <div class="small text-muted">
                                        <span class="badge badge-secondary mr-1">
                                            Pending: <?= $taskStats['Pending'] ?>
                                        </span>

                                        <span class="badge badge-warning mr-1">
                                            In Progress: <?= $taskStats['In Progress'] ?>
                                        </span>

                                        <span class="badge badge-success">
                                            Completed: <?= $taskStats['Completed'] ?>
                                        </span>
                                    </div>

                                    <div class="mt-2 small text-warning">
                                        View all tasks →
                                    </div>
                                </div>

                                <!-- ICON -->
                                <div class="col-auto">
                                    <i class="fas fa-tasks fa-2x text-gray-300"></i>
                                </div>

                            </div>
                        </div>
                    </div>

                </a>

            </div>
        <?php endif; ?>
        </div>

        <div class="row">
            <div class="col-12">

                <div class="card shadow-sm mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            Tasks to do today
                        </h6>

                        <a href="<?= BASE_URL ?>/admin/tasks/list.php"
                            class="btn btn-sm btn-outline-primary">
                            View All
                        </a>
                    </div>

                    <div class="card-body p-0">

                        <?php if (empty($todayTasks)): ?>
                            <div class="p-3 text-muted text-center">
                                No tasks assigned today.
                            </div>
                        <?php else: ?>

                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Task</th>
                                            <th>Assigned To</th>
                                            <th>Status</th>
                                            <th class="text-right">Actions</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php foreach ($todayTasks as $task): ?>
                                            <tr>

                                                <!-- TASK -->
                                                <td>
                                                    <strong><?= htmlspecialchars($task['title']) ?></strong>
                                                    <div class="small text-muted">
                                                        By <?= htmlspecialchars($task['assigned_by_email']) ?>
                                                    </div>
                                                </td>

                                                <!-- ASSIGNED TO -->
                                                <td>
                                                    <?= htmlspecialchars($task['assigned_to_email']) ?>
                                                </td>

                                                <!-- STATUS -->
                                                <td>
                                                    <?php
                                                    $badge = match ($task['status']) {
                                                        'Completed' => 'success',
                                                        'In Progress' => 'warning',
                                                        default => 'secondary'
                                                    };
                                                    ?>
                                                    <span class="badge badge-<?= $badge ?>">
                                                        <?= $task['status'] ?>
                                                    </span>
                                                </td>

                                                <!-- ACTIONS -->
                                                <td class="text-right">



                                                    <!-- MORE -->
                                                    <!-- MORE (Only if assigned by me) -->
                                                    <?php if ($task['assigned_by'] == $_SESSION['user']['id']): ?>
                                                        <a href="<?= BASE_URL ?>/admin/tasks/list.php"
                                                            class="btn btn-sm btn-outline-secondary">
                                                            More
                                                        </a>
                                                    <?php endif; ?>

                                                </td>

                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </div>

</div>
</div>



<?php require_once '../includes/footer.php'; ?>