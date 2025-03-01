<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'config.php';

$isAdmin = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT role, profile_photo, username FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $isAdmin = ($user['role'] === 'admin');
}

$url = $_SERVER['PHP_SELF'];
$isFlare = (stripos($url, 'flare.php') !== false);

$backgrounds = scandir('backgrounds/'); // get all files into an array
$backgrounds = array_diff($backgrounds, array('.', '..')); // remove . and .. from array

// Randomly choose a background image
$i = rand(2, count($backgrounds) - 1); // generate random number size of the array
$selectedBg = "$backgrounds[$i]"; // set variable equal to which random filename was chosen

$activeFile = substr($url, 0, -4);
$activeFile = substr($activeFile, 1);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adventure Blog</title>
    <!-- Favicon -->
    <link rel="icon" href="https://adventure-blog.ddns.net/favicon.ico?v=2" />

    <!-- Tailwind CSS -->
    <link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">

    <!-- Custom JS -->
    <script src="js/script.js"></script>

    <!-- Tagify CSS and JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css">
    <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>

    <!-- Background Image -->
    <style type="text/css">
        body {
            background: url(backgrounds/<?php echo $selectedBg; ?>);
            background-attachment: fixed;
            background-size: cover;
            background-repeat: no-repeat;
        }

        .<?php echo $activeFile; ?> {
            color: aquamarine;
        }
    </style>
</head>

<body>

    <header>
        <h1>Adventure Blogs</h1>
        <!-- Navigation bar -->
        <div class="nav-container">
            <nav>
                <?php if ($isFlare !== true): ?>
                    <a href="index.php" class="index">Home</a>
                    <a href="view_all.php" class="view_all">View All</a>
                    <a href="map.php" class="map">Map</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="dashboard.php" class="dashboard">Create Post</a>
                        <a href="flare.php">🔥 Flare (Beta) 🔥</a>
                        <a href="logout.php">Sign Out</a>

                    <?php endif; ?>

                <?php endif; ?>
                <?php if ($isFlare === true): ?>
                    <a href="index.php">&larr; Back to Adventure Blogs</a>
                <?php endif; ?>
                <!-- User Info -->
                <div class="user-info">

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <img src="<?php echo !empty($user['profile_photo']) ? htmlspecialchars($user['profile_photo']) : 'profile_photos/default_profile.png'; ?>" alt="Profile Photo" class="profile-photo-header">
                        <a id="user_profile" href="<?php echo 'user_profile.php?username=' . $user['username'] ?>" class="username"><?php echo htmlspecialchars($user['username']); ?> <?php if ($user['role'] == 'admin') {
                                                                                                                                                                                            echo '(' . htmlspecialchars($user['role']) . ')';
                                                                                                                                                                                        } ?></a>
                    <?php else: ?>
                        <a href="login.php" class="login">Sign In</a>
                        <a href="register.php" class="register">Register</a>
                    <?php endif; ?>

                </div>
            </nav>
        </div>
    </header>
</body>
<script>
    let $$ = document.querySelector.bind(document);
</script>

</html>