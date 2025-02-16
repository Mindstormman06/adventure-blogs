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
    SELECT posts.id, posts.title, posts.content, posts.image_path, users.username, posts.created_at 
    FROM posts 
    JOIN users ON posts.user_id = users.id 
    ORDER BY posts.created_at DESC
");
$posts = $stmt->fetchAll();

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

<div class="container">
    <h1>Recent Posts</h1>

    <?php foreach ($posts as $post): 
        $fileExtension = pathinfo($post['image_path']);
        $isVideo = in_array(strtolower($fileExtension['extension']), $videoFileTypes);
        $isAudio = in_array(strtolower($fileExtension['extension']), $audioFileTypes);
        $isImage = !$isVideo && !$isAudio && !empty($post['image_path']);
        $postUserID = $post['username'];

        // Convert Markdown to HTML safely
        $postContent = $Parsedown->text($post['content']);

        // Format the post date and calculate time ago
        $formattedPostDate = formatDate($post['created_at'], 'UTC');
        $timeAgo = timeAgo($post['created_at'], 'UTC');
    ?>
        <div class="post">
            <h2><?php echo htmlspecialchars($post['title']); ?></h2>
            <p><i>By <?php echo htmlspecialchars($post['username']); ?></i></p>
            <p>
                <i>Posted on 
                <span class="post-time" data-time="<?php echo htmlspecialchars($post['created_at']); ?>">
                    <?php echo $formattedPostDate; ?> (<?php echo $timeAgo; ?>) 
                </span>
                <b>Only accurate in PST (For now)</b>
                </i>
            </p>
            <p><?php echo $postContent; ?></p> <!-- Render Markdown -->

            <?php if ($isVideo): ?>
                <video style="max-width: 500px; max-height: 500px; min-width: 100px; min-height: 100px;" controls src="<?php echo htmlspecialchars($post['image_path']); ?>" autoplay muted loop>
                    Your browser does not support the video tag.
                </video>
            <?php endif; ?>

            <?php if ($isAudio): ?>
                <audio controls src="<?php echo htmlspecialchars($post['image_path']); ?>">
                    Your browser does not support the audio element.
                </audio>
            <?php endif; ?>

            <?php if ($isImage): ?>
                <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Failed to load image" style="max-width: 500px; max-height: 500px;">
            <?php endif; ?>

            <p><a class="btn" href="post.php?id=<?php echo $post['id']; ?>">View Comments</a></p>

            <?php if (isset($_SESSION['user_id']) && $user && ($_SESSION['username'] == $post['username'] || $user['role'] == 'admin')): ?>
                <p class="post_controls">
                    <a class="btn btn-warning" href="edit_post.php?id=<?php echo $post['id']; ?>">Edit</a>
                    <a class="btn btn-danger" href="delete_post.php?id=<?php echo $post['id']; ?>" 
                    onclick="return confirm('Are you sure you want to delete this post?');">Delete</a>
                </p>
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
