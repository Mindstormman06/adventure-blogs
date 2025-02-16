<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'config.php';

$isAdmin = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $isAdmin = ($user['role'] === 'admin');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adventure Blog</title>
    <link rel="icon" href="http://adventure-blog.ddns.net/favicon.ico?v=2" />
    <link rel="stylesheet" href="style.css">
    <script src="js/script.js"></script>
</head>
<body>
    <header>
    <nav>
    <a href="index.php">Home</a>
    <?php if (isset($_SESSION['user_id'])): ?>
        <?php if ($isAdmin): ?>
            <a href="admin.php">Admin Panel</a>
        <?php endif; ?>
        <a href="dashboard.php">Create Post</a>
        <a href="logout.php">Logout</a>
        <p class="lia" style="color:lightblue;margin: 0 15px;font-weight:bold;">Logged in as:<a href="edit_user.php" style="color:lightblue;margin: 0 15px;font-weight:bold;"><?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest'; ?> (<?php echo $user['role']?>)</a></p>


    <?php else: ?>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
    <?php endif; ?>
</nav>
    </header>
