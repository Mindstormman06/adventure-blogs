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
$audioFileTypes = ['mp3', 'wav', 'ogg', 'm4a', 'flac'];

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

$postFilesStmt = $pdo->query("SELECT post_id, file_path FROM post_files");
$postFiles = [];
while ($row = $postFilesStmt->fetch(PDO::FETCH_ASSOC)) {
    $postFiles[$row['post_id']][] = $row['file_path'];
}


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
            overflow: header;
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
            object-fit: contain;
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
<body>
<div class="container">

    <input type="text" id="search" class="search-bar" placeholder="Search posts...">

        
    <?php if (count($posts) > 0): ?>

        <div class="posts-grid">
            <?php foreach ($posts as $post): ?>

                <?php
                    $fileExtension = pathinfo($postFiles[$post['id']][0]);
                    $isVideo = in_array(strtolower($fileExtension['extension']), $videoFileTypes);
                    $isAudio = in_array(strtolower($fileExtension['extension']), $audioFileTypes);
                    $isImage = !$isVideo && !$isAudio && !empty($post['image_path']);
                ?>
                <div class="post-tile" data-username="<?php echo strtolower($post['username']); ?>" data-tags="<?php foreach ($tags1 as $tag) { if ($tag['post_id'] == $post['id']) { echo strtolower($tag['name']) . ' '; } } ?>" data-location="<?php echo strtolower($post['location_name']); ?>" data-content="<?php echo strtolower(strip_tags($post['content'])); ?>">

                    <!-- Title -->
                    <a href="<?php echo "post.php?id=" . $post['id']?>"><h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3></a>

                    <!-- Username -->
                    <p style="display: flex; align-items: center;" class="post-username">
                        <a href="<?php echo 'user_profile.php?username=' . $post['username']?>" class="post-user-link link-primary">
                            <?php echo htmlspecialchars($post['username']);?>
                            <img src="<?php echo !empty($post['profile_photo']) ? htmlspecialchars($post['profile_photo']) : 'profile_photos/default_profile.png'; ?>" 
                                alt="Profile Photo" class="profile-photo-post">
                        </a>
                    </p>

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
    
</div>
<?php include 'footer.php'; ?>
<script>
document.getElementById("search").addEventListener("input", function () {
    let query = this.value.toLowerCase().trim();
    console.log("Search Query:", query); // Debugging

    let posts = document.querySelectorAll(".post-tile");

    posts.forEach(post => {
        let username = post.getAttribute("data-username")?.toLowerCase() || "";
        let tags = post.getAttribute("data-tags")?.toLowerCase() || "";
        let location = post.getAttribute("data-location")?.toLowerCase() || "";
        let title = post.getAttribute("data-title")?.toLowerCase() || ""; // Ensure title exists

        console.log("Post Data:", { username, tags, location, title }); // Debugging

        let matches = false;

        if (query.startsWith("@")) {
            let searchTerm = query.substring(1); // Remove @
            matches = username.includes(searchTerm);
            console.log("Searching by Username:", searchTerm, matches);
        } else if (query.startsWith("#")) {
            let searchTerm = query.substring(1); // Remove #
            matches = tags.includes(searchTerm);
            console.log("Searching by Tag:", searchTerm, matches);
        } else {
            matches = location.includes(query) || title.includes(query);
            console.log("Searching by Title/Location:", query, matches);
        }

        post.style.display = matches ? "flex" : "none";
    });
});
</script>
</body>

