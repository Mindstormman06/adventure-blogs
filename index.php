<?php

// Start Session if it's not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Requirements
include 'header.php';
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

// Fetch user info if logged in
$user = null;
$userRole = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $userRole = $user ? $user['role'] : null;
}

// Allowed file types for media content
$videoFileTypes = ['mp4', 'ogg', 'webm', 'mov'];
$audioFileTypes = ['mp3', 'wav', 'ogg', 'm4a', 'flac'];

// Fetch all posts, tags, and post files
$posts = $postObj->getAllPosts();
$tags1 = $postObj->getAllTags();
list($postFiles, $postFilesOriginal) = $postObj->getAllPostFiles();

?>

<body>
    <div class="container">
        <h1>Recent Posts</h1>

        <?php foreach ($posts as $post):
            // Check type of uploaded files
            if (isset($postFiles[$post['id']]) && is_array($postFiles[$post['id']])) {
                $fileExtension = pathinfo($postFiles[$post['id']][0]);
                $isVideo = in_array(strtolower($fileExtension['extension']), $videoFileTypes);
                $isAudio = in_array(strtolower($fileExtension['extension']), $audioFileTypes);
                $isImage = !$isVideo && !$isAudio && !empty($post['image_path']);
            } else {
                $isVideo = false;
                $isAudio = false;
                $isImage = false;
            }
            $i = -1;

            // Set User ID
            $postUserID = htmlspecialchars($post['username']);

            // Get the post tags
            foreach ($tags1 as $tags) {
                if ($tags['post_id'] == $post['id']) {
                    $postTag = $tags;
                }
            }

            // Convert Markdown to HTML safely
            $postContent = $postObj->formatPostContent($post['content']);

            // Format the post date and calculate time ago
            $formattedPostDate = $postObj->formatDate($post['created_at'], 'UTC');
            $timeAgo = $postObj->timeAgo($post['created_at'], 'UTC');
        ?>
            <div class="post" 
                data-username="<?php echo strtolower($postUserID); ?>" 
                data-tags="<?php foreach ($tags1 as $tag) {
                    if ($tag['post_id'] == $post['id']) {
                        echo strtolower(htmlspecialchars($tag['name'])) . ' ';
                    }
                } ?>" 
                data-location="<?php echo strtolower(htmlspecialchars($post['location_name'])); ?>" 
                data-content="<?php echo strtolower(strip_tags($post['content'])); ?>">

                <!-- Post title -->
                <h2 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h2>

                <!-- Debug Prints -->
                <?php // echo '<pre>'; print_r($postTags); echo '</pre>'; 
                ?>
                <?php // echo '<pre>'; print_r($postTag); echo '</pre>'; 
                ?>
                <?php // echo '<pre>'; print_r($post); echo '</pre>'; 
                ?>

                <!-- Posted by user -->
                <p style="display: flex; align-items: center;" class="post-username">
                    <a href="<?php echo 'user_profile.php?username=' . htmlspecialchars($post['username']); ?>" class="post-user-link link-primary">
                        <?php echo htmlspecialchars($post['username']); ?>
                        <img src="<?php echo !empty($post['profile_photo']) ? htmlspecialchars($post['profile_photo']) : 'profile_photos/default_profile.png'; ?>"
                            alt="Profile Photo" class="profile-photo-post">
                    </a>
                </p>

                <!-- Posted Date/Time -->
                <p>
                    <i>Posted on
                        <span class="post-time" data-time="<?php echo htmlspecialchars($post['created_at']); ?>">
                            <?php echo $formattedPostDate; ?>
                        </span>
                    </i>
                </p>

                <!-- Post Tags -->

                <?php if (!empty($postTag)): ?>
                    <p class="post-tags"><strong>Tags:</strong>
                        <?php
                        foreach ($tags1 as $tags) {
                            if ($tags['post_id'] == $post['id']) {
                                echo '#' . htmlspecialchars($tags['name']) . " ";
                            }
                        }
                        ?>
                    </p>
                <?php endif; ?>

                <!-- Display location if available -->
                <?php if (!empty($post['location_name']) && !empty($post['latitude']) && !empty($post['longitude'])): ?>
                    <p class="post-location">
                        <strong>Location:</strong>
                        <a href="view_location.php?lat=<?php echo htmlspecialchars($post['latitude']); ?>&lng=<?php echo htmlspecialchars($post['longitude']); ?>&name=<?php echo htmlspecialchars($post['location_name']); ?>">
                            <?php echo htmlspecialchars($post['location_name']); ?>
                        </a>
                    </p>
                <?php endif; ?>

                <!-- Render Markdown -->
                <p><?php echo $postContent; ?></p>

                <!-- Display media content -->
                <div class="post-media">
                    <?php if (isset($postFiles[$post['id']]) && is_array($postFiles[$post['id']])): ?>
                        <?php foreach ($postFiles[$post['id']] as $file): ?>
                            <?php
                            $fileExtension = pathinfo($file, PATHINFO_EXTENSION);
                            $isVideo = in_array(strtolower($fileExtension), $videoFileTypes);
                            $isAudio = in_array(strtolower($fileExtension), $audioFileTypes);
                            $isImage = !$isVideo && !$isAudio;
                            ?>

                            <?php if ($isVideo): ?>
                                <video controls src="<?php echo htmlspecialchars($file); ?>" autoplay muted loop></video>
                            <?php endif; ?>

                            <?php if ($isAudio): ?>
                                <div>
                                    <?php
                                    $i += 1; // Increment $i for each file
                                    $originalName = $postFilesOriginal[$post['id']][$i];
                                    $displayName = mb_strimwidth($originalName, 0, 36, "...");
                                    ?>
                                    <p style="margin-top: 5px"><?php echo htmlspecialchars($displayName); ?></p>
                                    <audio controls src="<?php echo htmlspecialchars($file); ?>" loop></audio>
                                </div>
                            <?php endif; ?>

                            <?php if ($isImage): ?>
                                <img src="<?php echo htmlspecialchars($file); ?>" alt="Uploaded Image">
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="flex gap-2 mt-2 md:mt-0">

                    <!-- View Comments Button -->
                    <a class="" href="post.php?id=<?php echo $post['id']; ?>">
                        <button type="button" class="btn btn-primary">💬</button>
                    </a>

                    <!-- Post Controls -->
                    <?php if (isset($_SESSION['user_id']) && $user && ($_SESSION['username'] == $post['username'] || $user['role'] == 'admin')): ?>
                        <a href="edit_post.php?id=<?php echo $post['id']; ?>">
                            <button type="button" class="btn btn-warning">
                                ✏️
                            </button>
                        </a>

                        <a href="delete_post.php?id=<?php echo $post['id']; ?>" onclick="return confirm('Are you sure you want to delete this post?');">
                            <button type="button" class="btn btn-danger">
                                🗑️
                            </button>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php include 'footer.php'; ?>

    <!-- Include the new JavaScript file -->
    <script src="js/PostHandler.js"></script>
</body>

</html>