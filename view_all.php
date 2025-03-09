<?php include 'header.php'; ?>
<?php include 'config.php'; ?>
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'config.php';
require 'vendor\erusev\parsedown\Parsedown.php'; // Include Parsedown for Markdown support
require_once 'vendor/autoload.php'; // Include Composer autoload
require 'models/Post.php'; // Include the Post class

// Configure HTMLPurifier
$config = HTMLPurifier_Config::createDefault();
$purifier = new HTMLPurifier($config);

// Initialize Parsedown
$Parsedown = new Parsedown(); // Initialize Parsedown

// Create Post object
$postObj = new Post($pdo, $Parsedown, $purifier);

$user = null;
$userRole = null;

// If user is logged in, fetch their user information
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $userRole = $user ? $user['role'] : null;
}

// Approved file types for media content
$videoFileTypes = ['mp4', 'ogg', 'webm', 'mov'];
$audioFileTypes = ['mp3', 'wav', 'ogg', 'm4a', 'flac'];

// Fetch all posts, tags, and post files
$posts = $postObj->getAllPosts();
$tags1 = $postObj->getAllTags();
list($postFiles, $postFilesOriginal) = $postObj->getAllPostFiles();

?>

<body>
    <div class="container">

        <input type="text" id="search" class="search-bar" placeholder="Search posts... (e.g., @username, #tag, location, title)">

        <!-- Display user posts in a tile view -->
        <?php if (count($posts) > 0): ?>
            <div class="posts-grid">

                <?php foreach ($posts as $post):

                    // Check type of uploaded files
                    $fileExtension = pathinfo($postFiles[$post['id']][0]);
                    $isVideo = in_array(strtolower($fileExtension['extension']), $videoFileTypes);
                    $isAudio = in_array(strtolower($fileExtension['extension']), $audioFileTypes);
                    $isImage = !$isVideo && !$isAudio && !empty($post['image_path']);
                ?>
                    <div class="post-tile" data-title="<?php echo htmlspecialchars($post['title']); ?>" data-username="<?php echo strtolower(htmlspecialchars($post['username'])); ?>" data-tags="<?php foreach ($tags1 as $tag) {
                                                                                                                                                                                                        if ($tag['post_id'] == $post['id']) {
                                                                                                                                                                                                            echo strtolower(htmlspecialchars($tag['name'])) . ' ';
                                                                                                                                                                                                        }
                                                                                                                                                                                                    } ?>" data-location="<?php echo strtolower(htmlspecialchars($post['location_name'])); ?>" data-content="<?php echo strtolower(strip_tags($post['content'])); ?>">

                        <!-- Title -->
                        <a href="<?php echo "post.php?id=" . $post['id'] ?>">
                            <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                        </a>

                        <!-- Username -->
                        <p style="display: flex; align-items: center;" class="post-username">
                            <a href="<?php echo 'user_profile.php?username=' . htmlspecialchars($post['username']); ?>" class="post-user-link link-primary">
                                <?php echo htmlspecialchars($post['username']); ?>
                                <img src="<?php echo !empty($post['profile_photo']) ? htmlspecialchars($post['profile_photo']) : 'profile_photos/default_profile.png'; ?>"
                                    alt="Profile Photo" class="profile-photo-post">
                            </a>
                        </p>

                        <!-- Posted Date -->
                        <p><i><?php echo $postObj->formatDate($post['created_at']); ?></i></p>

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
                            $gridClass = ($mediaCount >= 6 ? 'grid-3x2' : $mediaCount >= 4) ? 'grid-2x2' : ($mediaCount == 2 ? 'grid-1x2' : 'grid-1x1');
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

    </div>
    <?php include 'footer.php'; ?>

    <!-- JavaScript to filter posts by search query -->
    <script src="js/SearchHandler.js"></script>
</body>

</html>