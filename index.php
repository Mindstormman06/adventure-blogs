<?php include 'header.php'; ?>
<?php include 'config.php'; ?>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'config.php';
require 'vendor\erusev\parsedown\Parsedown.php'; // Include Parsedown for Markdown support

$user = null;
$userRole = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $userRole = $user ? $user['role'] : null;
}

$videoFileTypes = ['mp4', 'ogg', 'webm', 'mov'];
$audioFileTypes = ['mp3', 'wav', 'ogg', 'm4a'];

$stmt = $pdo->query("
    SELECT posts.id, posts.title, posts.content, posts.image_path, users.username, posts.created_at, users.profile_photo, location_name, latitude, longitude
    FROM posts 
    JOIN users ON posts.user_id = users.id 
    ORDER BY posts.created_at DESC
");
$posts = $stmt->fetchAll();

$stmt1 = $pdo->query("
    SELECT tags.id, tags.name, post_tags.post_id
    FROM tags
    INNER JOIN post_tags ON tags.id = post_tags.tag_id");
$tags1 = $stmt1->fetchAll();



$Parsedown = new Parsedown(); // Initialize Parsedown

// Helper functions
function timeAgo($datetime, $timezone = 'UTC') {
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

function formatDate($datetime, $timezone = 'UTC') {
    $date = new DateTime($datetime, new DateTimeZone($timezone));
    return $date->format('Y/m/d');  // Format as YYYY/MM/DD
}
?>
<head>
    <style>

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

    </style>
</head>
<body>
<div class="container">
    <h1>Recent Posts</h1>

    <?php foreach ($posts as $post): 
        $fileExtension = pathinfo($post['image_path']);
        $isVideo = in_array(strtolower($fileExtension['extension']), $videoFileTypes);
        $isAudio = in_array(strtolower($fileExtension['extension']), $audioFileTypes);
        $isImage = !$isVideo && !$isAudio && !empty($post['image_path']);
        $postUserID = $post['username'];


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
        <div class="post" data-username="<?php echo strtolower($post['username']); ?>" data-tags="<?php foreach ($tags1 as $tag) { if ($tag['post_id'] == $post['id']) { echo strtolower($tag['name']) . ' '; } } ?>" data-location="<?php echo strtolower($post['location_name']); ?>" data-content="<?php echo strtolower(strip_tags($post['content'])); ?>">
            
            <!-- Post title -->
            <h2 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h2>

            <!-- Debug Prints -->
            <?php // echo '<pre>'; print_r($postTags); echo '</pre>'; ?>
            <?php // echo '<pre>'; print_r($postTag); echo '</pre>'; ?>
            <?php // echo '<pre>'; print_r($post); echo '</pre>'; ?>

            <!-- Posted by user -->
            <p style="display: flex; align-items: center;" class="post-username">
                <a href="<?php echo 'user_profile.php?username=' . $post['username']?>" class="post-user-link">
                    <i>By <?php echo htmlspecialchars($post['username']);?></i>
                    <img src="<?php echo !empty($post['profile_photo']) ? htmlspecialchars($post['profile_photo']) : 'profile_photos/default_profile.png'; ?>" 
                        alt="Profile Photo" class="profile-photo-post">
                </a>
            </p>

            <!-- Posted Date/Time -->
            <p>
                <i>Posted on 
                <span class="post-time" data-time="<?php echo htmlspecialchars($post['created_at']); ?>">
                    <?php echo $formattedPostDate; ?> (<?php echo $timeAgo; ?>) 
                </span>
                <b>Only accurate in PST (For now)</b>
                </i>
            </p>

            <!-- Post Tags -->
            <p class="post-tags"><strong>Tags:</strong> 
                <?php 
                    foreach ($tags1 as $tags) {
                        if ($tags['post_id'] == $post['id']) {   
                            echo '#' . htmlspecialchars($tags['name']) . " ";
                        }
                    }
                ?>
            </p>
            <!-- Render Markdown -->
            <p><?php echo $postContent; ?></p> 

            <!-- Display media content -->
            <?php if ($isVideo): ?>
                <video style="max-width: 500px; max-height: 500px;" controls src="<?php echo htmlspecialchars($post['image_path']); ?>" autoplay muted loop>
                    Your browser does not support the video tag.
                </video>
            <?php endif; ?>

            <?php if ($isAudio): ?>
                <audio controls src="<?php echo htmlspecialchars($post['image_path']); ?>">
                    Your browser does not support the audio element.
                </audio>
            <?php endif; ?>

            <?php if ($isImage): ?>
                <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Failed to load image" style="max-width: 65%; max-height: 65%;" class="post-image">
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

            <!-- View Comments Button -->
            
                <a class="" href="post.php?id=<?php echo $post['id']; ?>">
                    <button type="button" class="text-white bg-green-700 hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800">View Comments</button>
                </a>
            

            <!-- Post Controls -->
            <?php if (isset($_SESSION['user_id']) && $user && ($_SESSION['username'] == $post['username'] || $user['role'] == 'admin')): ?>
            <div class="flex gap-2 mt-2 md:mt-0">
                <a href="edit_post.php?id=<?php echo $post['id']; ?>">
                    <button type="button" class="text-black bg-yellow-400 hover:bg-yellow-500 focus:outline-none focus:ring-4 focus:ring-yellow-300 font-medium rounded-full text-sm px-5 py-2.5 text-center dark:focus:ring-yellow-900">
                        Edit
                    </button>
                </a>

                <a href="delete_post.php?id=<?php echo $post['id']; ?>" onclick="return confirm('Are you sure you want to delete this post?');">
                    <button type="button" class="text-white bg-red-700 hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 font-medium rounded-full text-sm px-5 py-2.5 text-center dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900">
                        Delete
                    </button>
                </a>
            </div>
    <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php include 'footer.php'; ?>

<!-- Add your JS here -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll(".post-time").forEach(function(element) {
        let utcTime = element.getAttribute("data-time");

        if (!utcTime) return; // Skip if no timestamp found

        let dateObj = new Date(utcTime);  // Create a JS Date object from the UTC timestamp
        if (isNaN(dateObj.getTime())) {  // Check for invalid date
            console.error("Invalid date format for:", utcTime);
            element.innerText = "Error loading time";
            return;
        }

        // Format the date as YYYY/MM/DD
        let formattedDate = dateObj.getFullYear() + '/' + 
                            ('0' + (dateObj.getMonth() + 1)).slice(-2) + '/' + 
                            ('0' + dateObj.getDate()).slice(-2);

        // Get the time difference (e.g., "2 hours ago", "3 days ago", etc.)
        let timeAgo = getTimeAgo(dateObj);

        // Display the formatted date and time difference
        element.innerText = `${formattedDate} (${timeAgo})`;
    });

    function getTimeAgo(dateObj) {
        let now = new Date();
        let diff = now - dateObj;

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
