<?php include 'header.php'; ?>
<?php include 'config.php'; ?>
<?php
// Start Session if it's not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Requirements
require 'config.php';
require 'vendor\erusev\parsedown\Parsedown.php'; // Include Parsedown for Markdown support

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

// Fetch all posts
$stmt = $pdo->query("
    SELECT posts.id, posts.title, posts.content, posts.image_path, users.username, posts.created_at, users.profile_photo, location_name, latitude, longitude
    FROM posts 
    JOIN users ON posts.user_id = users.id 
    ORDER BY posts.created_at DESC
");
$posts = $stmt->fetchAll();

// Fetch all tags
$stmt1 = $pdo->query("
    SELECT tags.id, tags.name, post_tags.post_id
    FROM tags
    INNER JOIN post_tags ON tags.id = post_tags.tag_id");
$tags1 = $stmt1->fetchAll();

// Fetch all post files
$postFilesStmt = $pdo->query("SELECT post_id, file_path, original_filename FROM post_files");
$postFiles = [];
while ($row = $postFilesStmt->fetch(PDO::FETCH_ASSOC)) {
    $postFiles[$row['post_id']][] = $row['file_path'];
    $postFilesOriginal[$row['post_id']][] = $row['original_filename'];
}

// Initialize Parsedown
$Parsedown = new Parsedown(); // Initialize Parsedown

// Function to calculate how long ago a post was submitted
function timeAgo($datetime, $timezone = 'UTC')
{
    $now = new DateTime("now", new DateTimeZone($timezone));
    $postTime = new DateTime($datetime, new DateTimeZone($timezone));
    $diff = $now->diff($postTime);

    if ($diff->y > 0) {
        return $diff->y . " year" . ($diff->y > 1 ? "s" : "") . " ago";
    }
    if ($diff->m > 0) {
        return $diff->m . " month" . ($diff->m > 1 ? "s" : "") . " ago";
    }
    if ($diff->d > 0) {
        if ($diff->d >= 7) {
            $weeks = floor($diff->d / 7);
            return $weeks . " week" . ($weeks > 1 ? "s" : "") . " ago";
        }
        return $diff->d . " day" . ($diff->d > 1 ? "s" : "") . " ago";
    }
    if ($diff->h > 0) {
        return $diff->h . " hour" . ($diff->h > 1 ? "s" : "") . " ago";
    }
    if ($diff->i > 0) {
        return $diff->i . " minute" . ($diff->i > 1 ? "s" : "") . " ago";
    }
    return "Just now";
}

// Function to format date as YYYY/MM/DD
function formatDate($datetime, $timezone = 'UTC')
{
    $date = new DateTime($datetime, new DateTimeZone($timezone));
    return $date->format('Y/m/d');  // Format as YYYY/MM/DD
}
?>

<body>
    <div class="container">
        <h1>Recent Posts</h1>

        <?php foreach ($posts as $post):
            // Check type of uploaded files
            $fileExtension = pathinfo($postFiles[$post['id']][0]);
            $isVideo = in_array(strtolower($fileExtension['extension']), $videoFileTypes);
            $isAudio = in_array(strtolower($fileExtension['extension']), $audioFileTypes);
            $isImage = !$isVideo && !$isAudio && !empty($post['image_path']);
            $i = -1;

            // Set User ID
            $postUserID = $post['username'];

            // Get the post tags
            foreach ($tags1 as $tags) {
                if ($tags['post_id'] == $post['id']) {
                    $postTag = $tags;
                }
            }

            // Convert Markdown to HTML safely
            $postContent = $Parsedown->text($post['content']);

            // Format the post date and calculate time ago
            $formattedPostDate = formatDate($post['created_at'], 'UTC');
            $timeAgo = timeAgo($post['created_at'], 'UTC');
        ?>
            <div class="post" data-username="<?php echo strtolower($post['username']); ?>" data-tags="<?php foreach ($tags1 as $tag) {
                                                                                                            if ($tag['post_id'] == $post['id']) {
                                                                                                                echo strtolower($tag['name']) . ' ';
                                                                                                            }
                                                                                                        } ?>" data-location="<?php echo strtolower($post['location_name']); ?>" data-content="<?php echo strtolower(strip_tags($post['content'])); ?>">

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
                    <a href="<?php echo 'user_profile.php?username=' . $post['username'] ?>" class="post-user-link link-primary">
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
                        <a href="view_location.php?lat=<?php echo $post['latitude']; ?>&lng=<?php echo $post['longitude']; ?>&name=<?php echo $post['location_name']; ?>">
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

                <!-- View Comments Button -->
                <a class="" href="post.php?id=<?php echo $post['id']; ?>">
                    <button type="button" class="btn btn-primary" style="margin-top: 10px">View Comments</button>
                </a>

                <!-- Post Controls -->
                <?php if (isset($_SESSION['user_id']) && $user && ($_SESSION['username'] == $post['username'] || $user['role'] == 'admin')): ?>
                    <div class="flex gap-2 mt-2 md:mt-0">
                        <a href="edit_post.php?id=<?php echo $post['id']; ?>">
                            <button type="button" class="btn btn-warning">
                                Edit
                            </button>
                        </a>

                        <a href="delete_post.php?id=<?php echo $post['id']; ?>" onclick="return confirm('Are you sure you want to delete this post?');">
                            <button type="button" class="btn btn-danger">
                                Delete
                            </button>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <?php include 'footer.php'; ?>

    <!-- Script to display the time difference in a human-readable format -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll(".post-time").forEach(function(element) {
                let pstTime = element.getAttribute("data-time");

                if (!pstTime) return; // Skip if no timestamp found

                let dateObjPST = new Date(pstTime); // Stored PST timestamp
                if (isNaN(dateObjPST.getTime())) { // Check for invalid date
                    console.error("Invalid date format for:", pstTime);
                    element.innerText = "Error loading time";
                    return;
                }

                // Step 1: Convert PST → UTC (Add 8 hours)
                let dateObjUTC = new Date(dateObjPST.getTime() + (8 * 60 * 60 * 1000));

                // Step 2: Convert UTC → Local Time (Based on viewer's timezone)
                let localTime = new Date(dateObjUTC.getTime() - dateObjUTC.getTimezoneOffset() * 60000);

                // console.log("Stored PST Time:", pstTime);
                // console.log("Converted UTC Time:", dateObjUTC.toISOString());
                // console.log("Viewer's Timezone:", Intl.DateTimeFormat().resolvedOptions().timeZone);
                // console.log("Local Time:", localTime.toLocaleString());

                // Format the local date as YYYY/MM/DD
                let formattedDate = localTime.getFullYear() + '/' +
                    ('0' + (localTime.getMonth() + 1)).slice(-2) + '/' +
                    ('0' + localTime.getDate()).slice(-2);

                // Get the time difference (e.g., "2 hours ago", "3 days ago", etc.)
                let timeAgo = getTimeAgo(localTime);

                // Display the formatted date and time difference
                element.innerText = `${formattedDate} (${timeAgo})`;
            });

            function getTimeAgo(localTime) {
                let now = new Date(); // Local time
                let diff = now - localTime;

                // Calculate time difference in milliseconds
                let minutes = Math.floor(diff / (1000 * 60));
                let hours = Math.floor(diff / (1000 * 60 * 60));
                let days = Math.floor(diff / (1000 * 60 * 60 * 24));
                let weeks = Math.floor(days / 7);
                let months = Math.floor(days / 30);
                let years = Math.floor(days / 365);

                if (years > 0) return `${years} year${years > 1 ? 's' : ''} ago`;
                if (months > 0) return `${months} month${months > 1 ? 's' : ''} ago`;
                if (weeks > 0) return `${weeks} week${weeks > 1 ? 's' : ''} ago`;
                if (days > 0) return `${days} day${days > 1 ? 's' : ''} ago`;
                if (hours > 0) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
                if (minutes > 0) return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
                return "Just now";
            }
        });
    </script>



</body>

</html>