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

<?php
    // Array of background images
    $bg = array('bg-01.jpg', 'bg-02.jpg'); // array of filenames

    // Randomly choose a background image
    $i = rand(0, count($bg)-1); // generate random number size of the array
    $selectedBg = "$bg[$i]"; // set variable equal to which random filename was chosen
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adventure Blog</title>
    <!-- Favicon -->
    <link rel="icon" href="http://adventure-blog.ddns.net/favicon.ico?v=2" />

    <!-- Tailwind CSS -->
    <link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">

    <!-- Custom JS -->
    <script src="js/script.js"></script>

    <!-- Background Image -->
    <style type="text/css">
        
        body{
        background: url(backgrounds/<?php echo $selectedBg; ?>);
        background-attachment: fixed;
        }
        
    </style>
</head>

<body>
    <header>

        <!-- Navigation bar -->
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
        
        <!-- User Info -->
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="user-info">
                <img src="<?php echo !empty($user['profile_photo']) ? htmlspecialchars($user['profile_photo']) : 'profile_photos/default_profile.png'; ?>" alt="Profile Photo" class="profile-photo-header">
                <a href="<?php echo 'user_profile.php?username=' . $_SESSION['username']?>" class="username"><?php echo htmlspecialchars($_SESSION['username']); ?> <?php if ($user['role'] == 'admin') { echo '(' . htmlspecialchars($user['role']) . ')';} ?></a>
            </div>
        <?php endif; ?>

    </header>
</body>
