<?php
include 'header.php';
include 'config.php';
require 'vendor\erusev\parsedown\Parsedown.php'; // Include Parsedown for Markdown support


if (!isset($_GET['username'])) {
    die("User not found.");
}


$videoFileTypes = ['mp4', 'ogg', 'webm', 'mov'];
$audioFileTypes = ['mp3', 'wav', 'ogg', 'm4a', 'flac'];
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

$stmt1 = $pdo->query("
    SELECT tags.id, tags.name, post_tags.post_id
    FROM tags
    INNER JOIN post_tags ON tags.id = post_tags.tag_id");
$tags1 = $stmt1->fetchAll();

$postFilesStmt = $pdo->query("SELECT post_id, file_path FROM post_files");
$postFiles = [];
while ($row = $postFilesStmt->fetch(PDO::FETCH_ASSOC)) {
    $postFiles[$row['post_id']][] = $row['file_path'];
}
$Parsedown = new Parsedown(); // Initialize Parsedown

?>

<head>
    <style>

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

        /* Container for the posts grid */
        .posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        /* Individual post tile styling */
        .post-tile {
            display: flex;
            flex-direction: column;
            justify-content: space-between; /* Ensures button stays at bottom */
            height: 500px; /* Adjust as needed to fit your content */
            margin-bottom: 20px;
            border-radius: 15px;
            border: 2px solid #ddd;
            padding: 20px;
            transition: transform 0.3s ease;

        }

        .post-content {
            overflow: hidden;
            text-overflow: ellipsis;
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
            max-width: 275px;
            max-height: 200px;
            object-fit: cover;
            margin-top: 10px;
            border-radius: 10px;
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

        .profile-photo-post {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            object-fit: cover;
            margin-left: 5px; /* Space between username and profile picture */
            border: 2px solid black;
        }

        .post-user-link {
            display: flex;
            align-items: center;
            text-decoration: none; /* Remove underline */
            color: black; /* Make text black */
            font-style: normal; /* Ensure normal text style */
        }

        .search-bar {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            font-size: 16px;
        }
        /* Media Grid Container */
        .media-grid {
            display: grid;
            gap: 5px;
            width: 100%;
            height: 200px; /* Consistent height for all media blocks */
        }

        /* Different layouts based on file count */
        .grid-2x2 {
            grid-template-columns: repeat(2, 1fr);
            grid-template-rows: repeat(2, 1fr);
        }

        .grid-1x2 {
            grid-template-columns: repeat(2, 1fr);
        }

        .grid-1x1 {
            grid-template-columns: 1fr;
        }

        /* Ensuring media elements have consistent size */
        .media-item {
            width: 100%;
            height: 100%;
            object-fit: cover; /* Crops images/videos instead of stretching */
            border-radius: 10px;
            aspect-ratio: 16/9;
        }
        audio.media-item {
            height: 40px; /* Control the height of the audio player */
            object-fit: contain; /* Keep audio controls contained */
            border-radius: 5px;
        }
       
    </style>
</head>

<div class="container">

    <!-- Display user profile information -->
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
    <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->

    <!-- Display user posts in a tile view -->
    
    <?php if (count($posts) > 0): ?>

        <div class="posts-grid">
            <?php foreach ($posts as $post): ?>

                <?php
                    $fileExtension = pathinfo($postFiles[$post['id']][0]);
                    $isVideo = in_array(strtolower($fileExtension['extension']), $videoFileTypes);
                    $isAudio = in_array(strtolower($fileExtension['extension']), $audioFileTypes);
                    $isImage = !$isVideo && !$isAudio && !empty($post['image_path']);
                ?>
                <div class="post-tile">
                    
                    <!-- Title -->
                    <a href="<?php echo "post.php?id=" . $post['id']?>"><h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3></a>
                    
                    <!-- Posted Date -->
                    <p><i><?php echo date('F j, Y', strtotime($post['created_at'])); ?></i></p>

                    <!-- Tags -->
                    <p class="post-tags"><strong>Tags:</strong> 
                        <?php 
                            foreach ($tags1 as $tags) {
                                if ($tags['post_id'] == $post['id']) {   
                                    echo '#' . htmlspecialchars($tags['name']) . " ";
                                }
                            }
                        ?>
                    </p>
                    
                    <!-- Display media content -->
                    <?php if (isset($postFiles[$post['id']]) && is_array($postFiles[$post['id']])): ?>
                        <?php 
                            $mediaCount = count($postFiles[$post['id']]); 
                            $gridClass = $mediaCount >= 4 ? 'grid-2x2' : ($mediaCount == 2 ? 'grid-1x2' : 'grid-1x1');
                        ?>
                        
                        <div class="media-grid <?php echo $gridClass; ?>">
                            <?php foreach ($postFiles[$post['id']] as $file): ?>
                                <?php 
                                    $fileExtension = pathinfo($file, PATHINFO_EXTENSION);
                                    $isVideo = in_array(strtolower($fileExtension), $videoFileTypes);
                                    $isAudio = in_array(strtolower($fileExtension), $audioFileTypes);
                                    $isImage = !$isVideo && !$isAudio;
                                ?>

                                <?php if ($isVideo): ?>
                                    <video src="<?php echo htmlspecialchars($file); ?>" class="media-item" autoplay muted loop></video>
                                <?php endif; ?>

                                <?php if ($isAudio): ?>
                                    <audio controls class="media-item" src="<?php echo htmlspecialchars($file); ?>" loop>
                                        Your browser does not support the audio element.
                                    </audio>
                                <?php endif; ?>

                                <?php if ($isImage): ?>
                                    <img src="<?php echo htmlspecialchars($file); ?>" class="media-item" alt="Image">
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No posts found for this user.</p>
    <?php endif; ?>
    
</div>

<?php include 'footer.php'; ?>
