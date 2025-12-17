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
                AND u.status = 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return [
                'success' => false,
                'errors' => ['Invalid email or password']
            ];
        }

        if (!password_verify($password, $user['password'])) {
            return [
                'success' => false,
                'errors' => ['Invalid email or password']
            ];
        }

        // login success
        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'role_id' => $user['role_id'],
            'role_name' => $user['role_name']
        ];

        return [
            'success' => true
        ];
    }

    public function logout()
    {
        session_unset();
        session_destroy();
    }
}
