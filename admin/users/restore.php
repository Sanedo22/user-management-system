<?php
require_once '../../config/database.php';
require_once '../../includes/UserService.php';

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit;
}
$db = (new Database())->getConnection();
$userService = new UserService($db);
$result = $userService->restoreUser($_GET['id']);
header("Location: list.php");
exit;
