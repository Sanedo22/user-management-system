<?php
require_once '../../includes/repo/auth.php';
requireLogin();
requireRole(['User']);

require_once '../../config/database.php';

$db = (new Database())->getConnection();
$userId = $_SESSION['user']['id'];

$errors = [];
$success = '';

// fetch user
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$baseUrl = '/user_management-system/user-management-system';

$profileImage = !empty($user['profile_img'])
    ? $baseUrl . '../admin/uploads/profiles/' . $user['profile_img']
    : $baseUrl . '../admin/uploads/profiles/default.png';

/* -----------------------------
   UPDATE PROFILE
------------------------------*/
if (isset($_POST['upload_image'])) {

    $profileImg = $user['profile_img'];

    if (!empty($_FILES['profile_img']['name'])) {
        $ext = pathinfo($_FILES['profile_img']['name'], PATHINFO_EXTENSION);
        $fileName = 'user_' . $userId . '.' . $ext;
        $uploadPath = '../../admin/uploads/profiles/' . $fileName;
        move_uploaded_file($_FILES['profile_img']['tmp_name'], $uploadPath);
        $profileImg = $fileName;
    }

    $sql = "UPDATE users SET profile_img=? WHERE id=?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$profileImg, $userId]);
    $success = 'Profile image updated successfully';
}

/* -----------------------------
   CHANGE PASSWORD
------------------------------*/
if (isset($_POST['change_password'])) {

    $current = $_POST['current_password'];
    $new     = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if ($new !== $confirm) {
        $errors[] = 'Passwords do not match';
    } elseif (!password_verify($current, $user['password'])) {
        $errors[] = 'Current password incorrect';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password=? WHERE id=?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$hash, $userId]);
        $success = 'Password changed successfully';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>

    <link href="<?= BASE_URL ?>/assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/sb-admin-2.min.css" rel="stylesheet">

    <!-- Bootstrap (same version used in admin) -->
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-light">

    <div class="container py-4">

        <h2 class="mb-4">My Dashboard</h2>

        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success position-relative">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>




        <!-- PROFILE SUMMARY -->
        <div class="card mb-4">
            <div class="card-body text-center">
                <img src="<?= htmlspecialchars($profileImage) ?>"
                    class="rounded-circle mb-3"
                    style="width:120px;height:120px;object-fit:cover;">
                <h5 class="mb-1">
                    <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                </h5>
                <p class="text-muted mb-0">
                    <?= htmlspecialchars($user['email']) ?>
                </p>
            </div>
        </div>

        <div class="row">

            <!-- Upload Image -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">Upload Profile Image</div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <input type="file" name="profile_img" class="form-control-file mb-2" accept="image/*">
                            <button type="submit" name="upload_image" class="btn btn-primary btn-sm">
                                Upload
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Change Password -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">Change Password</div>
                    <div class="card-body">
                        <form method="post">
                            <input type="password" name="current_password" class="form-control mb-2" placeholder="Current Password" required>
                            <input type="password" name="new_password" class="form-control mb-2" placeholder="New Password" required>
                            <input type="password" name="confirm_password" class="form-control mb-2" placeholder="Confirm Password" required>
                            <button type="submit" name="change_password" class="btn btn-warning btn-sm">
                                Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>

        <!-- SECURITY -->
        <div class="card mb-4">
            <div class="card-body">
                <h5>Security</h5>

                <?php if ($_SESSION['user']['twofa_enabled']): ?>
                    <div class="alert alert-success">
                        Two-Factor Authentication is enabled.
                    </div>
                    <button class="btn btn-warning btn-sm" onclick="confirmDisable2FA()">
                        Disable 2FA
                    </button>

                    <form id="disable2faForm"
                        method="post"
                        action="<?= BASE_URL ?>/admin/twofa/disable.php"
                        style="display:none;">
                        <input type="password" name="password" id="disable2faPassword">
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning">
                        Two-Factor Authentication is not enabled.
                    </div>
                    <a href="<?= BASE_URL ?>/admin/twofa/setup.php" class="btn btn-primary btn-sm">
                        Enable 2FA
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <a href="../logout.php" class="btn btn-danger btn-sm">
            Logout
        </a>

    </div>

    <script>
        function confirmDisable2FA() {
            Swal.fire({
                title: 'Disable Two-Factor Authentication?',
                input: 'password',
                inputPlaceholder: 'Confirm your password',
                inputAttributes: {
                    autocapitalize: 'off'
                },
                showCancelButton: true,
                confirmButtonText: 'Disable',
                confirmButtonColor: '#f6c23e',
                showLoaderOnConfirm: true,
                preConfirm: (password) => {
                    if (!password) {
                        Swal.showValidationMessage('Password is required');
                        return false;
                    }
                    document.getElementById('disable2faPassword').value = password;
                    document.getElementById('disable2faForm').submit();
                }
            });
        }

        //success message
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(a => {
                a.style.transition = 'opacity .3s';
                a.style.opacity = 0;
                setTimeout(() => a.remove(), 150);
            });
        }, 1000);
    </script>

</body>

</html>