<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'config.php';

$isAdmin = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT role, profile_photo FROM users WHERE id = ?");
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
    <link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    

    <link rel="stylesheet" href="style.css">
    <script src="js/script.js"></script>


    <style>
        /* Style for the header */
        .profile-photo-header {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
            border: 1px solid white;
        }
        .user-info {
            position: absolute;
            right: 20px;
            display: flex;
            align-items: center;
        }
        .username {
            color: lightblue;
            font-weight: bold;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <header>
        <!-- Centered navigation -->
        <div class="nav-container">
            <nav>
                <a href="index.php">Home</a>
                <a href="view_all.php">View All</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php">Create Post</a>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                <?php endif; ?>
            </nav>
        </div>
        
        <!-- User info on the right -->
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="user-info">
                <img src="<?php echo !empty($user['profile_photo']) ? htmlspecialchars($user['profile_photo']) : 'profile_photos/default_profile.png'; ?>" alt="Profile Photo" class="profile-photo-header">
                <a href="<?php echo 'user_profile.php?username=' . $_SESSION['username']?>" class="username"><?php echo htmlspecialchars($_SESSION['username']); ?> <?php if ($user['role'] == 'admin') { echo '(' . htmlspecialchars($user['role']) . ')';} ?></a>
            </div>
        <?php endif; ?>
    </header>
