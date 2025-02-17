<?php
include 'header.php';
include 'config.php';

if (!isset($_GET['username'])) {
    die("User not found.");
}

$username = $_GET['username']; // Get the username from the URL

// Fetch user information
$stmt = $pdo->prepare("SELECT id, username, email, profile_photo, instagram_link, website_link FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
// echo '<pre>'; print_r($user); echo '</pre>';
// echo '<pre>'; print_r($_SESSION); echo '</pre>';


if (!$user) {
    die("User not found.");
}

// Fetch user posts
$stmt = $pdo->prepare("SELECT id, title, image_path, created_at FROM posts WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$posts = $stmt->fetchAll();

$videoFileTypes = ['mp4', 'ogg', 'webm', 'mov'];
$audioFileTypes = ['mp3', 'wav', 'ogg', 'm4a'];
?>

<head>
    <style>
        /* Container for the posts grid */
        .posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        /* Individual post tile styling */
        .post-tile {
            border: 1px solid #ddd;
            padding: 20px;
            background-color: #f9f9f9;
            text-align: center;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .post-tile:hover {
            transform: translateY(-5px);
        }

        /* Profile Section */
        .profile-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .profile-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ddd;
            margin-bottom: 10px;
        }

        .profile-links a {
            display: inline-block;
            margin: 0 10px;
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }

        .profile-links a:hover {
            text-decoration: underline;
        }

        /* Image inside post tile */
        .post-image, .post-video {
            max-width: 100%;
            max-height: 200px;
            object-fit: cover;
            margin-top: 10px;
        }

        /* Button styling */
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
        }

        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<div class="container">
    <div class="profile-container">
        <!-- Display Profile Photo -->
        <?php if (!empty($user['profile_photo'])): ?>
            <img src="<?php echo htmlspecialchars($user['profile_photo']); ?>" alt="Profile Photo" class="profile-photo mx-auto">
        <?php else: ?>
            <img src="profile_photos/default_profile.png" alt="Default Profile" class="profile-photo">
        <?php endif; ?>

        <h1><?php echo htmlspecialchars($user['username']); ?></h1>

        <!-- Display Social Media Links -->
        <div class="profile-links">
            <?php if (!empty($user['instagram_link'])): ?>
                <a href="<?php echo htmlspecialchars($user['instagram_link']); ?>" target="_blank">Instagram</a>
            <?php endif; ?>
            <?php if (!empty($user['website_link'])): ?>
                <a href="<?php echo htmlspecialchars($user['website_link']); ?>" target="_blank">Website</a>
            <?php endif; ?>
        </div>
        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user['id']): ?>
            <a class="btn" href="edit_user.php">Edit Profile</a>
        <?php endif; ?>
    </div>

    <!-- Display user posts in a tile view -->
    <div class="user-posts">
        <?php if (count($posts) > 0): ?>
            <div class="posts-grid">
                <?php foreach ($posts as $post): ?>
                    <?php
                        $fileExtension = pathinfo($post['image_path']);
                        $isVideo = in_array(strtolower($fileExtension['extension']), $videoFileTypes);
                        $isAudio = in_array(strtolower($fileExtension['extension']), $audioFileTypes);
                        $isImage = !$isVideo && !$isAudio && !empty($post['image_path']);
                    ?>

                    <div class="post-tile">
                        <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                        <p><i>Posted on: <?php echo date('F j, Y', strtotime($post['created_at'])); ?></i></p>
                        
                        <!-- Display media content -->
                        <?php if ($isVideo): ?>
                            <video controls src="<?php echo htmlspecialchars($post['image_path']); ?>" autoplay muted loop class="post-video">
                                Your browser does not support the video tag.
                            </video>
                        <?php endif; ?>

                        <?php if ($isAudio): ?>
                            <audio controls src="<?php echo htmlspecialchars($post['image_path']); ?>">
                                Your browser does not support the audio element.
                            </audio>
                        <?php endif; ?>

                        <?php if ($isImage): ?>
                            <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Failed to load image" class="post-image">
                        <?php endif; ?>

                        <a class="btn" href="post.php?id=<?php echo $post['id']; ?>">View Post</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No posts found for this user.</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
