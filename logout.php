<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'config.php';
require 'models/User.php'; // Include the User class

$userObj = new User($pdo);
$userObj->logout();

header("Location: login.php");
exit;
