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
?>

<!DOCTYPE html>
<html>

<head>
    <title>User Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
</head>

<body>
    <div class="container">

        <h2 class="dashboard-title">User Dashboard</h2>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <p><?= htmlspecialchars($success) ?></p>
            </div>
        <?php endif; ?>

        <!-- Profile Picture Section -->
        <div class="dashboard-section">
            <h3>Profile Picture</h3>
            <div class="profile-img-wrapper">
                <img src="<?= htmlspecialchars($profileImage) ?>"
                    alt="Profile Image"
                    class="profile-img">
            </div>
        </div>

        <!-- Edit Profile Section -->
        <div class="dashboard-section">
            <h3>Upload Image</h3>
            <form method="post" enctype="multipart/form-data" class="dashboard-form">
                <div class="form-group">
                    <label for="profile_img">Profile Image</label>
                    <input type="file" id="profile_img" name="profile_img" accept="image/*">
                </div>

                <button type="submit" name="upload_image" class="btn primary">Upload Image</button>
            </form>
        </div>

        <!-- Change Password Section -->
        <div class="dashboard-section">
            <h3>Change Password</h3>
            <form method="post" class="dashboard-form">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit" name="change_password" class="btn primary">Change Password</button>
            </form>
        </div>

        <!-- Logout Section -->
        <div class="dashboard-section">
            <a href="../logout.php" class="btn danger">Logout</a>
        </div>

    </div>
</body>

</html>