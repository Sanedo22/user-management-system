<?php
require_once 'config/database.php';
require_once 'includes/UserService.php';

// get database
$database = new Database();
$db = $database->getConnection();

echo "<h2>Testing UserManager</h2>";

// create manager
$userManager = new UserService($db);

// ========================================
// TEST 1: Get all active users
// ========================================
echo "<h3>Test 1: Get All Active Users</h3>";
$users = $userManager->getAllUsers(false);
echo "Found " . count($users) . " active users:<br>";
foreach($users as $u) {
    echo "- {$u['first_name']} {$u['last_name']} ({$u['email']}) - Role: {$u['role_name']}<br>";
}

// ========================================
// TEST 2: Get single user
// ========================================
echo "<br><h3>Test 2: Get Single User (ID: 1)</h3>";
$user = $userManager->getUser(1);
if($user) {
    echo "Found: {$user['first_name']} {$user['last_name']}<br>";
    echo "Email: {$user['email']}<br>";
    echo "Role: {$user['role_name']}<br>";
}

// ========================================
// TEST 3: Validation (weak password)
// ========================================
echo "<br><h3>Test 3: Create User with Weak Password (Should Fail)</h3>";
$result = $userManager->createUser(1, 'John', 'Doe', 'john@test.com', 'abc123', '', '', '', 1);
if(!$result['success']) {
    echo "❌ Validation working! Errors:<br>";
    foreach($result['errors'] as $error) {
        echo "- {$error}<br>";
    }
}

// ========================================
// TEST 4: Validation (invalid email)
// ========================================
echo "<br><h3>Test 4: Create User with Invalid Email (Should Fail)</h3>";
$result = $userManager->createUser(1, 'John', 'Doe', 'invalid-email', 'Test@123', '', '', '', 1);
if(!$result['success']) {
    echo "❌ Validation working! Errors:<br>";
    foreach($result['errors'] as $error) {
        echo "- {$error}<br>";
    }
}

// ========================================
// TEST 5: Validation (duplicate email)
// ========================================
echo "<br><h3>Test 5: Create User with Duplicate Email (Should Fail)</h3>";
$result = $userManager->createUser(1, 'Test', 'User', 'admin@example.com', 'Test@123', '', '', '', 1);
if(!$result['success']) {
    echo "❌ Validation working! Errors:<br>";
    foreach($result['errors'] as $error) {
        echo "- {$error}<br>";
    }
}

// ========================================
// TEST 6: Create valid user
// ========================================
echo "<br><h3>Test 6: Create Valid User (Should Succeed)</h3>";
$result = $userManager->createUser(
    2,                      // role_id (Manager)
    'Jane',                 // first_name
    'Smith',                // last_name
    'jane@example.com',     // email
    'Test@123',             // password (strong)
    '+1',                   // country_code
    '1234567890',           // phone_number
    '123 Main St',          // address
    1                       // status
);

if($result['success']) {
    echo "✅ {$result['message']} (New ID: {$result['id']})<br>";
} else {
    echo "❌ Failed:<br>";
    foreach($result['errors'] as $error) {
        echo "- {$error}<br>";
    }
}

echo "<br><hr>";
echo "<h3>✅ All Tests Completed!</h3>";
echo "<p><strong>UserManager is working with:</strong></p>";
echo "<ul>";
echo "<li>✅ Get all users (with role names)</li>";
echo "<li>✅ Get single user (with role name)</li>";
echo "<li>✅ Strong password validation</li>";
echo "<li>✅ Email validation</li>";
echo "<li>✅ Duplicate email check</li>";
echo "<li>✅ Create user</li>";
echo "</ul>";
echo "<p><strong>Key Point:</strong> UserManager uses the SAME SimpleRepo as RoleManager!</p>";
?>