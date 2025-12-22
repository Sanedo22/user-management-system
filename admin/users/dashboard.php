<?php
require_once '../../includes/auth.php';
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

$baseUrl = 'user_management-system/user-management-system'; 

$profileImage = !empty($user['profile_img'])
    ? $baseUrl . '/admin/uploads/profiles/' . $user['profile_img']
    : $baseUrl . '/admin/uploads/profiles/default.png';


// UPDATE PROFILE
if (isset($_POST['update_profile'])) {

    $first = trim($_POST['first_name']);
    $last  = trim($_POST['last_name']);
    $phone = trim($_POST['phone_number']);
    $addr  = trim($_POST['address']);

    if (!$first || !$last) {
        $errors[] = 'First and Last name required';
    }

    $profileImg = $user['profile_img'];

    if (!empty($_FILES['profile_img']['name'])) {
        $ext = pathinfo($_FILES['profile_img']['name'], PATHINFO_EXTENSION);
        $fileName = 'user_' . $userId . '.' . $ext;
        $uploadPath = '../../admin/uploads/profiles/' . $fileName;
        move_uploaded_file($_FILES['profile_img']['tmp_name'], $uploadPath);
        $profileImg = $fileName;
    }

    if (!$errors) {
        $sql = "UPDATE users SET first_name=?, last_name=?, phone_number=?, address=?, profile_img=? WHERE id=?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$first, $last, $phone, $addr, $profileImg, $userId]);
        $success = 'Profile updated successfully';
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
</head>
<body>

<h2>User Dashboard</h2>

<?php if ($errors): ?>
    <ul style="color:red">
        <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if ($success): ?>
    <p style="color:green"><?= htmlspecialchars($success) ?></p>
<?php endif; ?>

<hr>

<h3>Profile Picture</h3>

<img src="<?= htmlspecialchars($profileImage) ?>"
     alt="Profile Image"
     width="150"
     height="150"
     style="border-radius:50%; object-fit:cover; border:1px solid #ccc;">

<h3>Edit Profile</h3>

<form method="post" enctype="multipart/form-data">
    <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required><br><br>
    <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required><br><br>
    <input type="text" name="phone_number" value="<?= htmlspecialchars($user['phone_number']) ?>"><br><br>
    <textarea name="address"><?= htmlspecialchars($user['address']) ?></textarea><br><br>

    <input type="file" name="profile_img"><br><br>

    <button name="update_profile">Update Profile</button>
</form>

<hr>

<h3>Change Password</h3>

<form method="post">
    <input type="password" name="current_password" placeholder="Current Password" required><br><br>
    <input type="password" name="new_password" placeholder="New Password" required><br><br>
    <input type="password" name="confirm_password" placeholder="Confirm Password" required><br><br>

    <button name="change_password">Change Password</button>
</form>

<hr>

<a href="../logout.php">Logout</a>

</body>
</html>
