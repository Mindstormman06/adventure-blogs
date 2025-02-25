<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"]) && !isset($_COOKIE["remember_token"])) {

    $token = $_COOKIE["remember_token"];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["role"] = $user["role"];
    } else {
        setcookie("remember_token", "", time() - 3600, "/", "", true, true);
    }

    header("Location: login.php");
    exit;
}
?>
