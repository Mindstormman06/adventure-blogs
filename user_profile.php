<?php
include 'header.php';
include 'config.php';
require 'vendor\erusev\parsedown\Parsedown.php'; // Include Parsedown for Markdown support
require_once 'vendor/autoload.php'; // Include Composer autoload
require 'models/Post.php'; // Include the Post class

if (!isset($_GET['username'])) {
    die("User not found.");
}

// Approved file types for media content
$videoFileTypes = ['mp4', 'ogg', 'webm', 'mov'];
$audioFileTypes = ['mp3', 'wav', 'ogg', 'm4a', 'flac'];

// Get the username from the URL
$username = $_GET['username'];

// Fetch user information
$stmt = $pdo->prepare("SELECT id, username, email, profile_photo, instagram_link, website_link FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

// Configure HTMLPurifier
$config = HTMLPurifier_Config::createDefault();
$purifier = new HTMLPurifier($config);

// Initialize Parsedown
$Parsedown = new Parsedown(); // Initialize Parsedown

// Create Post object
$postObj = new Post($pdo, $Parsedown, $purifier);

// Fetch user posts, tags, and post files
$posts = $postObj->getUserPosts($user['id']);
$tags1 = $postObj->getAllTags();
list($postFiles, $postFilesOriginal) = $postObj->getAllPostFiles();

?>

<div class="container">

    <!-- Display user profile information -->
    <div class="profile-container">
        <!-- Display Profile Photo -->
        <?php if (!empty($user['profile_photo'])): ?>
            <img src="<?php echo htmlspecialchars($user['profile_photo']); ?>" alt="Profile Photo" class="profile-photo mx-auto">
        <?php else: ?>
            <img src="profile_photos/default_profile.png" alt="Default Profile" class="profile-photo mx-auto">
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
                    <a href="<?php echo "post.php?id=" . $post['id'] ?>">
                        <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                    </a>

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