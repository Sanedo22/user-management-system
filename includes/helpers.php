<?php
// start session if not started
function startSession() {
    if(session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

function setFlashMessage($type, $message) {
    startSession();
    $_SESSION['flash_message'] = array(
        'type' => $type,  // success, error, warning, info
        'message' => $message
    );
}

function getFlashMessage() {
    startSession();
    if(isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

function displayFlashMessage() {
    $flash = getFlashMessage();
    if($flash) {
        $type = $flash['type'];
        $message = $flash['message'];
        
        $icon = 'info';
        if($type == 'success') $icon = 'success';
        if($type == 'error') $icon = 'error';
        if($type == 'warning') $icon = 'warning';
        
        echo "<script>
            Swal.fire({
                icon: '{$icon}',
                title: '" . ucfirst($type) . "',
                text: '{$message}',
                showConfirmButton: true
            });
        </script>";
    }
}

// redirect to another page
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// sanitize input data
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// check if user is logged in
function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']);
}

// get logged in user id
function getLoggedInUserId() {
    startSession();
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

// set logged in user
function setLoggedInUser($userId, $userName, $userEmail, $roleId) {
    startSession();
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $userName;
    $_SESSION['user_email'] = $userEmail;
    $_SESSION['role_id'] = $roleId;
}

// logout user
function logoutUser() {
    startSession();
    session_unset();
    session_destroy();
}

// format date for display
function formatDate($date) {
    if(empty($date)) {
        return '-';
    }
    return date('d M Y, h:i A', strtotime($date));
}

function formatDateShort($date) {
    if(empty($date)) {
        return '-';
    }
    return date('d M Y', strtotime($date));
}

// get status badge HTML
function getStatusBadge($status) {
    if($status == 1) {
        return '<span class="badge bg-success">Active</span>';
    } else {
        return '<span class="badge bg-danger">Inactive</span>';
    }
}

// generate random password
function generateRandomPassword($length = 10) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
    $password = '';
    $charLength = strlen($characters);
    
    for($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, $charLength - 1)];
    }
    
    return $password;
}

// hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// check if request is POST
function isPostRequest() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

// check if request is GET
function isGetRequest() {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

// get POST data
function getPostData($key, $default = '') {
    return isset($_POST[$key]) ? sanitizeInput($_POST[$key]) : $default;
}

// get GET data
function getGetData($key, $default = '') {
    return isset($_GET[$key]) ? sanitizeInput($_GET[$key]) : $default;
}

// display validation errors
function displayErrors($errors) {
    if(is_array($errors) && count($errors) > 0) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        echo '<strong>Error!</strong><ul class="mb-0">';
        foreach($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul>';
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}

// debug function (only for development)
function dd($data) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    die();
}

// check if string is valid email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// generate CSRF token
function generateCsrfToken() {
    startSession();
    if(!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// verify CSRF token
function verifyCsrfToken($token) {
    startSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// get CSRF token input field
function csrfField() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}
?>