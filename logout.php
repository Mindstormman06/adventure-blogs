<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

session_destroy();

setcookie("remember_token", "", time() - 3600, "/", "", false, true);

header("Location: login.php");
exit;
