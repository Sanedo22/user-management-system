<?php
require_once __DIR__ . '/../repo/repository.php';
require_once __DIR__ . '/RoleService.php';

class UserService
{
    private $repo;
    private $roleService;

    public function __construct($database)
    {
        $this->repo = new Repository($database, 'users');
        $this->roleService = new RoleService($database);
    }

    public function getAllUsers($showDeleted = false)
    {
        if ($showDeleted) {
            $sql = "SELECT u.*, r.name as role_name
                    FROM users u
                    LEFT JOIN roles r ON u.role_id = r.id
                    ORDER BY u.id DESC";
        } else {
            $sql = "SELECT u.*, r.name as role_name
                    FROM users u
                    LEFT JOIN roles r ON u.role_id = r.id
                    WHERE u.deleted_at IS NULL
                    ORDER BY u.id DESC";
        }

        $stmt = $this->repo->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUser($id)
    {
        $sql = "SELECT u.*, r.name as role_name
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE u.id = ? AND u.deleted_at IS NULL";

        $stmt = $this->repo->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserByEmail($email)
    {
        $sql = "SELECT * FROM users WHERE email = ? AND deleted_at IS NULL";
        $stmt = $this->repo->db->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createUser(
        $role_id,
        $firstname,
        $lastname,
        $email,
        $password,
        $countryCode,
        $phoneNumber,
        $address,
        $status,
        $profileImg = null
    ) {
        $errors = $this->validateUser($email, $password, $firstname, $lastname, $phoneNumber);

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $data = [
            'role_id'      => $role_id,
            'first_name'   => $firstname,
            'last_name'    => $lastname,
            'email'        => $email,
            'password'     => password_hash($password, PASSWORD_DEFAULT),
            'country_code' => $countryCode,
            'phone_number' => $phoneNumber,
            'address'      => $address,
            'status'       => $status,
            'profile_img'  => $profileImg
        ];

        $id = $this->repo->insert($data);

        if ($id) {
            // sync role
            $this->roleService->syncRoleStatus($role_id);

            return [
                'success' => true,
                'message' => 'User created successfully',
                'id'      => $id
            ];
        }

        return ['success' => false, 'errors' => ['Failed to create user']];
    }

    public function updateUser(
        $id,
        $role_id,
        $firstname,
        $lastname,
        $email,
        $countryCode,
        $phoneNumber,
        $address,
        $status,
        $profileImg = null,
        $newPassword = null
    ) {
        if (!isset($_SESSION['user'])) {
            return ['success' => false, 'errors' => ['Unauthorized']];
        }

        if ($id == ROOT_SUPER_ADMIN_ID) {
            return ['success' => false, 'errors' => ['Root Super Admin cannot be modified']];
        }

        if (!$targetUser) {
            return ['success' => false, 'errors' => ['User not found']];
        }

        $oldRoleId = $targetUser['role_id'];

        if ($_SESSION['user']['id'] == $id && (int)$role_id !== (int)$oldRoleId) {
            return ['success' => false, 'errors' => ['You cannot modify your own role']];
        }

        if (!$this->canEditUser($_SESSION['user'], $targetUser)) {
            return ['success' => false, 'errors' => ['You are not allowed to edit this user']];
        }

        $errors = $this->validateUser($email, $newPassword, $firstname, $lastname, $phoneNumber, $id);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $data = [
            'role_id'      => $role_id,
            'first_name'   => $firstname,
            'last_name'    => $lastname,
            'email'        => $email,
            'country_code' => $countryCode,
            'phone_number' => $phoneNumber,
            'address'      => $address,
            'status'       => $status
        ];

        if ($profileImg) {
            $data['profile_img'] = $profileImg;
        }

        if ($newPassword) {
            $data['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        $updated = $this->repo->update($id, $data);

        if ($updated) {
            // old role sync
            if ($oldRoleId != $role_id) {
                $this->roleService->syncRoleStatus($oldRoleId);
            }

            // new role sync
            $this->roleService->syncRoleStatus($role_id);

            return ['success' => true, 'message' => 'User updated successfully'];
        }

        return ['success' => false, 'errors' => ['Failed to update user']];
    }

    public function deleteUser($id)
    {
        if (!isset($_SESSION['user'])) {
            return ['success' => false, 'errors' => ['Unauthorized']];
        }

        if ($id == ROOT_SUPER_ADMIN_ID) {
            return ['success' => false, 'errors' => ['Root Super Admin cannot be deleted']];
        }

        if ($_SESSION['user']['id'] == $id) {
            return ['success' => false, 'errors' => ['You cannot delete your own account']];
        }

        $sql = "SELECT u.*, r.name AS role_name
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE u.id = ?";
        $stmt = $this->repo->db->prepare($sql);
        $stmt->execute([$id]);
        $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$targetUser) {
            return ['success' => false, 'errors' => ['User not found']];
        }

        if (!$this->canDeleteUser($_SESSION['user'], $targetUser)) {
            return ['success' => false, 'errors' => ['You are not allowed to delete this user']];
        }

        $deleted = $this->repo->delete($id);

        if ($deleted) {
            // update role count
            $this->roleService->syncRoleStatus($targetUser['role_id']);

            return ['success' => true, 'message' => 'User deleted successfully'];
        }

        return ['success' => false, 'errors' => ['Delete failed']];
    }

    public function restoreUser($id)
    {
        if ($id == ROOT_SUPER_ADMIN_ID) {
            return ['success' => false, 'errors' => ['Root Super Admin cannot be restored']];
        }

        $sql = "SELECT u.role_id, r.status, r.deleted_at
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE u.id = ?";
        $stmt = $this->repo->db->prepare($sql);
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return ['success' => false, 'errors' => ['User not found']];
        }

        if ($data['deleted_at'] !== null && $data['deleted_at'] !== '') {
            return ['success' => false, 'errors' => ['Cannot restore user: role is deleted']];
        }

        // if ((int)$data['status'] === 0) {
        //     return ['success' => false, 'errors' => [
        //         'Cannot restore user because the assigned role is inactive.'
        //     ]];
        // }

        $restored = $this->repo->restore($id);

        if ($restored) {
            // restore role count
            $this->roleService->syncRoleStatus($data['role_id']);

            return ['success' => true, 'message' => 'User restored successfully'];
        }

        return ['success' => false, 'errors' => ['Restore failed']];
    }

    /* =================== VALIDATION & PERMISSIONS (UNCHANGED) =================== */

    private function validateUser($email, $password, $firstname, $lastname, $phoneNumber, $excludeId = null)
    {
        $errors = [];

        if (empty($firstname)) $errors[] = 'First name is required';
        elseif (strlen($firstname) < 2) $errors[] = 'First name must be at least 2 characters';

        if (empty($lastname)) $errors[] = 'Last name is required';
        elseif (strlen($lastname) < 2) $errors[] = 'Last name must be at least 2 characters';

        if (empty($email)) $errors[] = 'Email is required';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format';
        elseif ($this->repo->exists('email', $email, $excludeId)) $errors[] = 'Email already exists';

        if ($password !== null) {
            if (empty($password)) $errors[] = 'Password is required';
            elseif (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';
        }

        if (empty($phoneNumber)) $errors[] = 'Phone number is required';
        elseif (!preg_match('/^[0-9]{7,15}$/', $phoneNumber))
            $errors[] = 'Phone number must be between 7 to 15 digits';

        return $errors;
    }

    private function canDeleteUser($loggedInUser, $targetUser)
    {
        // Nobody edits themselves
        if ($loggedInUser['id'] == $targetUser['id']) {
            return false;
        }

        // Root Super Admin can edit anyone
        if ($loggedInUser['id'] === ROOT_SUPER_ADMIN_ID) {
            return true;
        }

        // Super Admin can edit anyone except Super Admin
        if (
            $loggedInUser['role_name'] === 'Super Admin' &&
            $targetUser['role_name'] !== 'Super Admin'
        ) {
            return true;
        }

        // Admin can edit anyone except Admin & Super Admin
        if (
            $loggedInUser['role_name'] === 'Admin' &&
            !in_array($targetUser['role_name'], ['Admin', 'Super Admin'])
        ) {
            return true;
        }

        return false;
    }

    private function canEditUser($loggedInUser, $targetUser)
    {
        // Users CAN edit themselves (e.g. for profile updates)
        if ($loggedInUser['id'] == $targetUser['id']) {
            return true;
        }

        // Root Super Admin can edit anyone
        if ($loggedInUser['id'] === ROOT_SUPER_ADMIN_ID) {
            return true;
        }

        // Super Admin can edit anyone except Super Admin
        if (
            $loggedInUser['role_name'] === 'Super Admin' &&
            $targetUser['role_name'] !== 'Super Admin'
        ) {
            return true;
        }

        // Admin can edit anyone except Admin & Super Admin
        if (
            $loggedInUser['role_name'] === 'Admin' &&
            !in_array($targetUser['role_name'], ['Admin', 'Super Admin'])
        ) {
            return true;
        }

        return false;
    }


    public function isUserOnline($userId)
    {
        $sql = "SELECT COUNT(*)
        FROM user_sessions
        WHERE user_id = ?
        AND is_active = 1
        AND last_activity >= (NOW() - INTERVAL 30 MINUTE)";

        $stmt = $this->repo->db->prepare($sql);
        $stmt->execute([$userId]);

        return $stmt->fetchColumn() > 0;
    }

    /* =================== PASSWORD RESET =================== */

    public function createPasswordResetToken($userId, $token, $expiresAt)
    {
        // old token delete
        $sql = "DELETE FROM password_resets WHERE user_id = ?";
        $stmt = $this->repo->db->prepare($sql);
        $stmt->execute([$userId]);

        // save new token
        $sql = "INSERT INTO password_resets (user_id, token, expires_at, used) VALUES (?, ?, ?, 0)";
        $stmt = $this->repo->db->prepare($sql);
        return $stmt->execute([$userId, $token, $expiresAt]);
    }

    public function getPasswordResetByToken($token)
    {
        $sql = "SELECT * FROM password_resets 
                WHERE token = ? 
                AND expires_at > NOW() 
                AND used = 0";

        $stmt = $this->repo->db->prepare($sql);
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function markPasswordResetUsed($id)
    {
        $sql = "UPDATE password_resets SET used = 1 WHERE id = ?";
        $stmt = $this->repo->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    public function updateUserPassword($userId, $newHash)
    {
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $this->repo->db->prepare($sql);
        return $stmt->execute([$newHash, $userId]);
    }

    /* =================== PROFILE HELPER =================== */

    public function handleProfileUpdate($user, $postData, $filesData)
    {
        $userId = $user['id'];
        $messages = ['errors' => [], 'success' => ''];
        $db = $this->repo->db;

        // 1. image upload
        if (isset($postData['upload_image'])) {
            if (!empty($filesData['profile_img']['name'])) {
                $ext = pathinfo($filesData['profile_img']['name'], PATHINFO_EXTENSION);
                $fileName = 'user_' . $userId . '.' . $ext;
                
                $uploadPath = __DIR__ . '/../../admin/uploads/profiles/' . $fileName;
                
                if(move_uploaded_file($filesData['profile_img']['tmp_name'], $uploadPath)) {
                    $sql = "UPDATE users SET profile_img=? WHERE id=?";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$fileName, $userId]);
                    $messages['success'] = 'Profile image updated successfully';
                } else {
                     $messages['errors'][] = 'Failed to upload image';
                }
            }
        }

        // 1.5 remove image
        if (isset($postData['remove_image'])) {
            
            // get current img
            $currentImage = $user['profile_img'];

            // default img delete mat karna
            if (!empty($currentImage) && $currentImage !== 'default.jpg') {
                
                $filePath = __DIR__ . '/../../admin/uploads/profiles/' . $currentImage;
                
                // file delete
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            // set null in db
            $sql = "UPDATE users SET profile_img = NULL WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$userId]);
            
            $messages['success'] = 'Profile image removed successfully';
        }

        // 2. change password
        if (isset($postData['change_password'])) {
            $current = $postData['current_password'];
            $new     = $postData['new_password'];
            $confirm = $postData['confirm_password'];

            if ($new !== $confirm) {
                $messages['errors'][] = 'Passwords do not match';
            } elseif (!password_verify($current, $user['password'])) {
                $messages['errors'][] = 'Current password is incorrect';
            } else {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $this->updateUserPassword($userId, $hash);
                $messages['success'] = 'Password changed successfully';
            }
        }

        return $messages;
    }
}
