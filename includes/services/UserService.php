<?php
require_once '../../includes/repo/repository.php';
//require_once '../config/database.php';

class UserService
{
    private $repo;

    public function __construct($database)
    {
        $this->repo = new repository($database, 'users');
    }

    // private function getDatabase()
    // {
    //     $reflection = new ReflectionClass($this->repo);
    //     $property = $reflection->getProperty('db');
    //     $property->setAccessible(true);
    //     return $property->getValue($this->repo);
    // }

    // get all users
    public function getAllUsers($showDeleted = false)
    {
        if ($showDeleted) {
            // show ALL users (active + deleted)
            $sql = "SELECT u.*, r.name as role_name 
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.id 
                ORDER BY u.id DESC";
        } else {
            // show ONLY active users
            $sql = "SELECT u.*, r.name as role_name 
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.id 
                WHERE u.deleted_at IS NULL 
                ORDER BY u.id DESC";
        }

        $db = $this->repo->db;
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    //get one user
    public function getUser($id)
    {
        $sql = "SELECT u.*, r.name as role_name 
                    FROM users u 
                    LEFT JOIN roles r ON u.role_id = r.id 
                    WHERE u.id = ? AND u.deleted_at IS NULL";

        $db = $this->repo->db;
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    //get user by email
    public function getUserByEmail($email)
    {
        $sql = "SELECT * FROM users WHERE email = ? AND deleted_at IS NULL";
        $db = $this->repo->db;

        $stmt = $db->prepare($sql);
        $stmt->execute([$email]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // create user
    public function createUser($role_id, $firstname, $lastname, $email, $password, $countryCode, $phoneNumber, $address, $status, $profileImg = null)
    {
        $errors = $this->validateUser($email, $password, $firstname, $lastname, $phoneNumber);

        if (count($errors) > 0) {
            return array(
                'success' => false,
                'errors' => $errors
            );
        }

        $data = array(
            'role_id' => $role_id,
            'first_name' => $firstname,
            'last_name' => $lastname,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'country_code' => $countryCode,
            'phone_number' => $phoneNumber,
            'address' => $address,
            'status' => $status,
            'profile_img' => $profileImg
        );

        if ($profileImg) {
            $data['profile_img'] = $profileImg;
        }

        $newId = $this->repo->insert($data);
        if ($newId) {
            return array(
                'success' => true,
                'message' => 'User created successfully',
                'id' => $newId
            );
        }
        return array(
            'success' => false,
            'errors' => array('Failed to create user')
        );
    }

    //update user
    public function updateUser($id, $role_id, $firstname, $lastname, $email, $countryCode, $phoneNumber, $address, $status, $profileImg = null, $newPassword = null)
    {
        $existing = $this->repo->getOne($id);
        if (!$existing) {
            return array(
                'success' => false,
                'errors' => array('User not found')
            );
        }

        $sql = "SELECT u.*, r.name AS role_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.id = ?";

        $db = $this->repo->db;
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

        //RBAC check
        if (!isset($_SESSION['user']) || !$this->canEditUser($_SESSION['user'], $targetUser)) {
            return [
                'success' => false,
                'errors' => ['You are not allowed to edit this user']
            ];
        }

        $errors = $this->validateUser($email, $newPassword, $firstname, $lastname, $phoneNumber, $id);
        if (count($errors) > 0) {
            return array(
                'success' => false,
                'errors' => $errors
            );
        }

        $data = array(
            'role_id' => $role_id,
            'first_name' => $firstname,
            'last_name' => $lastname,
            'email' => $email,
            'country_code' => $countryCode,
            'phone_number' => $phoneNumber,
            'address' => $address,
            'status' => $status
        );
        if ($profileImg) {
            $data['profile_img'] = $profileImg;
        }
        if ($newPassword) {
            $data['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }
        $result = $this->repo->update($id, $data);
        if ($result) {
            return array(
                'success' => true,
                'message' => 'User updated successfully'
            );
        }
        return array(
            'success' => false,
            'errors' => array('Failed to update user')
        );
    }

    //delete user
    public function deleteUser($id)
    {
        if (!isset($_SESSION['user'])) {
            return [
                'success' => false,
                'errors' => ['Unauthorized']
            ];
        }

        // Fetch target user with role
        $sql = "SELECT u.*, r.name AS role_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.id = ?";

        $db = $this->repo->db;
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$targetUser) {
            return [
                'success' => false,
                'errors' => ['User not found']
            ];
        }

        // Check for RBAC
        if (!$this->canDeleteUser($_SESSION['user'], $targetUser)) {
            return [
                'success' => false,
                'errors' => ['You are not allowed to delete this user']
            ];
        }

        // Allow delete
        if ($this->repo->delete($id)) {
            return [
                'success' => true,
                'message' => 'User deleted successfully'
            ];
        }

        return [
            'success' => false,
            'errors' => ['Delete failed']
        ];
    }

    //restore user
    public function restoreUser($id)
    {
        $result = $this->repo->restore($id);
        if ($result) {
            return array(
                'success' => true,
                'message' => 'User restored successfully'
            );
        }
        return array(
            'success' => false,
            'errors' => array('Failed to restore user')
        );
    }

    // validate user data
    private function validateUser($email, $password, $firstname, $lastname, $phoneNumber, $excludeId = null)
    {
        $errors = array();

        //validate first name
        if (empty($firstname)) {
            $errors[] = 'First name is required';
        } else if (strlen($firstname) < 2) {
            $errors[] = 'First name must be at least 2 characters';
        }

        //validate last name
        if (empty($lastname)) {
            $errors[] = 'Last name is required';
        } else if (strlen($lastname) < 2) {
            $errors[] = 'Last name must be at least 2 characters';
        }

        //validate email
        if (empty($email)) {
            $errors[] = 'Email is required';
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        } else if ($this->repo->exists('email', $email, $excludeId)) {
            $errors[] = 'Email already exists';
        }

        //validate password
        if ($password !== null) {
            if (empty($password)) {
                $errors[] = 'Password is required';
            } else if (strlen($password) < 6) {
                $errors[] = 'Password must be at least 6 characters';
            } else if (!preg_match('/[A-Z]/', $password)) {
                $errors[] = 'Password must contain at least one uppercase letter';
            } else if (!preg_match('/[a-z]/', $password)) {
                $errors[] = 'Password must contain at least one lowercase letter';
            } else if (!preg_match('/[0-9]/', $password)) {
                $errors[] = 'Password must contain at least one number';
            } else if (!preg_match('/[\W]/', $password)) {
                $errors[] = 'Password must contain at least one special character';
            }
        }

        //validate phone number
        if (empty($phoneNumber)) {
            $errors[] = 'Phone number is required';
        } else if (!preg_match('/^[0-9]{7,15}$/', $phoneNumber)) {
            $errors[] = 'Phone number must be between 7 to 15 digits';
        }
        return $errors;
    }

    //invalidating old reset links
    public function invalidateOldPasswordResets($userId)
    {
        $sql = "UPDATE password_resets SET used = 1 WHERE user_id = ?";
        $db = $this->repo->db;
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
    }

    //password reset token generaton
    public function createPasswordResetToken($userId, $token, $expireAt)
    {
        $sql = "INSERT INTO password_resets(user_id, token, expires_at) VALUES (?, ?, ?)";

        $db = $this->repo->db;
        $stmt = $db->prepare($sql);

        return $stmt->execute([$userId, $token, $expireAt]);
    }

    //token validation
    public function getPasswordResetByToken($token)
    {
        $sql = "SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at >= NOW()";

        $db = $this->repo->db;
        $stmt = $db->prepare($sql);
        $stmt->execute([$token]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    //update password
    public function updateUserPassword($userId, $hashedPassword)
    {
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $db = $this->repo->db;
        $stmt = $db->prepare($sql);
        return $stmt->execute([$hashedPassword, $userId]);
    }

    //mark token used
    public function markPasswordResetUsed($resetId)
    {
        $sql = "UPDATE password_resets SET used = 1 WHERE id = ?";
        $db = $this->repo->db;
        $stmt = $db->prepare($sql);
        return $stmt->execute([$resetId]);
    }

    //permissions role based
    private function canDeleteUser($loggedInUser, $targetUser)
    {
        $actorRole = $loggedInUser['role_name'];
        $targetRole = $targetUser['role_name'];

        //Super admin GOD
        if ($targetRole === 'Super Admin') {
            return false;
        }

        if (
            $actorRole === 'Super Admin' &&
            $loggedInUser['id'] === $targetUser['id']
        ) {
            return false;
        }

        if ($actorRole === 'Super Admin') {
            return true;
        }

        if ($actorRole === 'Admin' && in_array($targetRole, ['Manager', 'User'])) {
            return true;
        }

        return false;
    }

    //edit permissions
    private function canEditUser($loggedInUser, $targetUser)
    {
        $actorRole = $loggedInUser['role_name'];
        $targetRole = $targetUser['role_name'];

        //Nobody edits Super Admin except Super Admin
        if ($targetRole === 'Super Admin' && $actorRole !== 'Super Admin') {
            return false;
        }

        //Admin cannot edit another Admin
        if ($actorRole === 'Admin' && $targetRole === 'Admin') {
            return false;
        }

        //Super Admin can edit anyone
        if ($actorRole === 'Super Admin') {
            return true;
        }

        //Admin can edit Manager & User
        if ($actorRole === 'Admin' && in_array($targetRole, ['Manager', 'User'])) {
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
}
