<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


include 'config.php';

$isAdmin = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT role, profile_photo, username, email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $isAdmin = ($user['role'] === 'admin');
} else {
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
    
        $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($user) {
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["role"] = $user["role"];
            header("Location: index.php");
            exit;
        } else {
            setcookie("remember_token", "", time() - 3600, "/", "", true, true);
        }
    }
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
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    
    <!-- Flowbite -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.3/flowbite.min.js"></script>

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
        <a href="index.php"><img src="logo_transparent.png" alt="Adventure Blogs Logo" class="logo" style="height: 75px;"></a>
        <!-- <h1><a href="index.php">Adventure Blogs</a></h1> -->
        <!-- Navigation bar -->
        <div class="nav-container">
            <nav>
                <?php if ($isFlare !== true): ?>
                    <a href="index.php" class="index">Home</a>
                    <a href="view_all.php" class="view_all">View All</a>
                    <a href="map.php" class="map">Map</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="dashboard.php" class="dashboard">Create Post</a>
                        <!-- <a href="flare.php">🔥 Flare (Beta) 🔥</a> -->                        
                    <?php endif; ?>

                <?php endif; ?>
                <?php if ($isFlare === true): ?>
                    <a href="index.php">&larr; Back to Adventure Blogs</a>
                <?php endif; ?>
                <!-- User Info -->
                <div class="user-info">

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <button id="dropdownUserAvatarButton" data-dropdown-toggle="dropdownAvatar" class="flex text-sm bg-gray-800 rounded-full md:me-0 focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600" type="button">
                        <span class="sr-only">Open user menu</span>
                        <img src="<?php echo htmlspecialchars($user['profile_photo']); ?>" alt="Profile Photo" class="profile-photo mx-auto w-8 h-8 rounded-full">
                        </button>

                        <!-- Dropdown menu -->
                        <div id="dropdownAvatar" class="z-10 hidden bg-gray-600 divide-y divide-gray-100 rounded-lg shadow-sm w-44 dark:bg-gray-700 dark:divide-gray-600">
                            <div class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                            <div><?php echo($user['username'])?></div>
                            <div class="font-medium truncate"><?php echo($user['email'])?></div>
                            </div>
                            <ul class="px-4 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownUserAvatarButton">
                            <li>
                                <a href="<?php echo 'user_profile.php?username=' . $user['username'] ?>" class="block px-4">View Profile</a>
                            </li>
                            <li>
                                <a href="edit_user.php" class="block px-4">User Settings</a>
                            </li>
                            </ul>
                            <div class="">
                            <a href="logout.php" class="block px-4 text-sm text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white">Sign out</a>
                            </div>
                        </div>
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

<script>
        document.addEventListener("DOMContentLoaded", function () {
            const $targetEl = document.getElementById('dropdownAvatar');
            const $triggerEl = document.getElementById('dropdownUserAvatarButton');

            if ($targetEl && $triggerEl) {
                const dropdown = new Dropdown($targetEl, $triggerEl, {
                    placement: 'bottom',
                    triggerType: 'click',
                });
            }
        });
    </script>

</html>

