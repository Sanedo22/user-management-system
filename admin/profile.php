<?php
require_once '../includes/repo/auth.php';
requireLogin();

require_once '../config/database.php';
require_once '../config/constants.php';

$db = (new Database())->getConnection();
$userId = $_SESSION['user']['id'];

$errors = [];
$success = '';

// fetch user
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$baseUrl = BASE_URL;

$profileImage = !empty($user['profile_img'])
    ? $baseUrl . '/admin/uploads/profiles/' . $user['profile_img']
    : $baseUrl . '/admin/uploads/profiles/default.png';

/* -----------------------------
   UPDATE PROFILE IMAGE
------------------------------*/
if (isset($_POST['upload_image'])) {

    $profileImg = $user['profile_img'];

    if (!empty($_FILES['profile_img']['name'])) {
        $ext = pathinfo($_FILES['profile_img']['name'], PATHINFO_EXTENSION);
        $fileName = 'user_' . $userId . '.' . $ext;
        $uploadPath = '../admin/uploads/profiles/' . $fileName;
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
        $errors[] = 'Current password is incorrect';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password=? WHERE id=?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$hash, $userId]);
        $success = 'Password changed successfully';
    }
}

$title = 'My Profile';
require_once '../includes/header.php';
?>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">My Profile</h1>

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
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <!-- PROFILE SUMMARY -->
    <div class="card shadow-sm mb-4">
        <div class="card-body d-flex align-items-center">
            <img src="<?= htmlspecialchars($profileImage) ?>"
                class="rounded-circle mr-4"
                style="width:100px;height:100px;object-fit:cover;">

            <div>
                <h4 class="mb-1">
                    <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                </h4>
                <p class="text-muted mb-0">
                    <?= htmlspecialchars($user['email']) ?>
                </p>
            </div>
        </div>
    </div>

    <div class="row">

        <!-- Update Image -->
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header font-weight-bold">
                    Update Profile Image
                </div>
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
            <div class="card shadow-sm mb-4">
                <div class="card-header font-weight-bold">
                    Change Password
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="password" name="current_password" class="form-control mb-2" placeholder="Current Password" required>
                        <input type="password" name="new_password" class="form-control mb-2" placeholder="New Password" required>
                        <input type="password" name="confirm_password" class="form-control mb-2" placeholder="Confirm Password" required>
                        <button type="submit" name="change_password" class="btn btn-warning btn-sm">
                            Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- SECURITY STATUS -->
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Security</h5>

                <?php if ($_SESSION['user']['twofa_enabled']): ?>
                    <div class="alert alert-success mb-3">
                        Two-Factor Authentication is enabled for your account.
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
                    <div class="alert alert-warning mb-3">
                        Two-Factor Authentication is not enabled.
                    </div>
                    <a href="<?= BASE_URL ?>/admin/twofa/setup.php" class="btn btn-warning btn-sm">
                        Enable 2FA
                    </a>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>

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
</script>


<?php require_once '../includes/footer.php'; ?>