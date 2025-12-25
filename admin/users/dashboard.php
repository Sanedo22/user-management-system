<?php
require_once '../../includes/repo/auth.php';
requireLogin();

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

// UPDATE PROFILE
if (isset($_POST['upload_image'])) {

    $profileImg = $user['profile_img'];

    if (!empty($_FILES['profile_img']['name'])) {
        $ext = pathinfo($_FILES['profile_img']['name'], PATHINFO_EXTENSION);
        $fileName = 'user_' . $userId . '.' . $ext;
        $uploadPath = '../../admin/uploads/profiles/' . $fileName;
        move_uploaded_file($_FILES['profile_img']['tmp_name'], $uploadPath);
        $profileImg = $fileName;
    }

    if (!$errors) {
        $sql = "UPDATE users SET profile_img=? WHERE id=?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$profileImg, $userId]);
        $success = 'Profile Image Uploaded successfully';
    }
}

// CHANGE PASSWORD
if (isset($_POST['change_password'])) {

    $current = $_POST['current_password'];
    $new     = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if ($new !== $confirm) {
        $errors[] = 'Passwords do not match';
    } else if (!password_verify($current, $user['password'])) {
        $errors[] = 'Current password incorrect';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password=? WHERE id=?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$hash, $userId]);
        $success = 'Password changed successfully';
    }
}

$title = 'User Dashboard';
require_once '../../includes/header.php';
?>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">User Dashboard</h1>

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

    <div class="row">

        <!-- Profile Card -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">

                    <img src="<?= htmlspecialchars($profileImage) ?>"
                        class="img-fluid rounded-circle mb-3"
                        style="width:150px;height:150px;object-fit:cover;">

                    <h5 class="mb-1">
                        <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                    </h5>

                    <p class="text-muted mb-0">
                        <?= htmlspecialchars($user['email']) ?>
                    </p>

                </div>
            </div>
        </div>

        <!-- Upload Image -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    Upload Profile Image
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <input type="file" name="profile_img" class="form-control-file" accept="image/*">
                        </div>
                        <button type="submit" name="upload_image" class="btn btn-primary btn-sm">
                            Upload
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Change Password -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    Change Password
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="form-group">
                            <input type="password" name="current_password" class="form-control" placeholder="Current Password" required>
                        </div>
                        <div class="form-group">
                            <input type="password" name="new_password" class="form-control" placeholder="New Password" required>
                        </div>
                        <div class="form-group">
                            <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-warning btn-sm">
                            Change Password
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
                    <a href="twofa/disable.php" class="btn btn-warning btn-sm">
                        Disable 2FA
                    </a>
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

    <a href="../logout.php" class="btn btn-danger btn-sm">
        Logout
    </a>

</div>

<?php require_once '../../includes/footer.php'; ?>