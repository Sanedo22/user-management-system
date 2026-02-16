<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class AuthService
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function login($email, $password)
    {
        $sql = "SELECT u.*, r.name AS role_name
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE u.email = ?
                AND u.deleted_at IS NULL
                ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return [
                'success' => false,
                'errors' => ['Invalid email or password or user deleted!']
            ];
        }

        if (!password_verify($password, $user['password'])) {
            return [
                'success' => false,
                'errors' => ['Invalid email or password or user deleted!']
            ];
        }

        $sql = "UPDATE user_sessions
                SET is_active = 0
                WHERE user_id = ?
                AND last_activity < (NOW() - INTERVAL 30 MINUTE)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$user['id']]);

        $sql = "SELECT COUNT(*)
                FROM user_sessions
                WHERE user_id = ?
                AND is_active = 1
                AND last_activity >= (NOW() - INTERVAL 30 MINUTE)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$user['id']]);
        $activeSessions = (int)$stmt->fetchColumn();

        if ($activeSessions >= 3) {
            return [
                'success' => false,
                'errors' => ['Maximum device limit reached']
            ];
        }

        if ($user['twofa_enabled']) {
            $_SESSION['pending_2fa_user'] = $user['id'];

            return [
                'success' => true,
                'twofa_required' => true
            ];
        }

        //login success
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id'            => $user['id'],
            'email'         => $user['email'],
            'role_id'       => $user['role_id'],
            'role_name'     => $user['role_name'],
            'twofa_enabled' => $user['twofa_enabled']
        ];

        $_SESSION['last_activity'] = time();

        $this->updateLastLogin($user['id']);

        // prevent duplicate session row
        $sql = "UPDATE user_sessions
                SET is_active = 0
                WHERE user_id = ?
                AND session_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$user['id'], session_id()]);

        // insert new session
        $sql = "INSERT INTO user_sessions
                (user_id, session_id, device_info, ip_address, last_activity, is_active)
                VALUES (?, ?, ?, ?, NOW(), 1)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $user['id'],
            session_id(),
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        ]);

        return [
            'success' => true
        ];
    }

    public function updateLastLogin($userId)
    {
        $sql = "UPDATE users SET last_login_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {

            $sql = "UPDATE user_sessions
                    SET is_active = 0
                    WHERE session_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([session_id()]);
        }

        session_unset();
        session_destroy();
    }
}
