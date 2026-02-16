<?php
require_once '../../includes/repo/auth.php';
requireLogin();

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../includes/services/UserService.php';

$db = (new Database())->getConnection();
$userService = new UserService($db); // Instantiate UserService
$userId = $_SESSION['user']['id'];

$errors = [];
$success = '';

// get user data
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$baseUrl = BASE_URL;

// check user image
if (!empty($user['profile_img'])) {
    $profileImage = $baseUrl . '/admin/uploads/profiles/' . $user['profile_img'];
} else {
    // else default image
    $profileImage = $baseUrl . '/admin/uploads/profiles/default.jpg';
}


// handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $userService->handleProfileUpdate($user, $_POST, $_FILES);
    
    if (!empty($result['errors'])) {
        $errors = array_merge($errors, $result['errors']);
    }
    
    if (!empty($result['success'])) {
        $success = $result['success'];
        
        // refresh user
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // update path
        $profileImage = !empty($user['profile_img'])
            ? $baseUrl . '/admin/uploads/profiles/' . $user['profile_img']
            : $baseUrl . '/admin/uploads/profiles/default.png';
    }
}

$title = 'My Profile';
require_once 'user-header.php';
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
                        <div class="mt-3">
                            <button type="submit" name="upload_image" class="btn btn-primary btn-sm">
                                Upload New
                            </button>
                            
                            <?php if (!empty($user['profile_img'])): ?>
                                <button type="button" class="btn btn-danger btn-sm ml-2" onclick="confirmRemoveImage(this)">
                                    Remove Image
                                </button>
                            <?php endif; ?>
                        </div>
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
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header font-weight-bold">
                    Security Settings
                </div>
                <div class="card-body">
                    <h5 class="card-title">Two-Factor Authentication</h5>

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

    </div>
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

    function confirmRemoveImage(btn) {
        swalConfirm({
            title: 'Remove Profile Picture?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            confirmText: 'Yes, remove it',
            confirmColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                let form = btn.closest('form');
                let input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'remove_image';
                input.value = '1';
                form.appendChild(input);
                form.submit();
            }
        });
    }
</script>


<?php require_once 'user_footer.php'; ?>