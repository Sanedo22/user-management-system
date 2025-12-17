<?php 
    require_once 'repository.php';

    class AuthService{

        private $repo;

        public function __construct($database)
        {
            $this->repo = new Repository($database, 'users');
        }

        public function login($email, $password){
            $sql = "SELECT u.*, r.name AS role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id
                    WHERE u.email = ? AND u.deleted_at IS NULL";

                    $stmt = $this->repo->db->prepare($sql);
                    $stmt->execute([$email]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    if(!$user){
                        return ['success' => false, 'message' => 'Invalid email or password.'];
                    }

                    if(!password_verify($password, $user['password'])){
                        return ['success' => false, 'message' => 'Invalid email or password.'];
                    }

                    if($user['status'] != 1){
                        return ['success' => false, 'message' => 'Your account is inactive. Please contact admin.'];
                    }

                    //login success
                    session_regenerate_id(true);

                    $_SESSION['user'] = [
                        'id' => $user['id'],
                        'email' => $user['email'],
                        'role_id' => $user['role_id'],
                        'role_name' => $user['role_name']
                    ];

                    $this->repo->db->prepare(
                        "UPDATE users SET last_login_at = NOW() WHERE id = ?"
                    )->execute([$user['id']]);

                    return ['success' => true];
        }

        public function logout(){
            session_unset();
            session_destroy();
            header('Location: /login.php');
            exit();
        }

    }
?>