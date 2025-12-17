<?php
require_once '../includes/AuthService.php';
require_once '../config/database.php';

$dbObj = new Database();
$db = $dbObj->getConnection();

$auth = new AuthService($db);
$auth->logout();

header('Location: login.php');
exit;
?>
