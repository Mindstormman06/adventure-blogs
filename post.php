<?php 
include 'header.php'; 
include 'config.php'; 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'vendor\erusev\parsedown\Parsedown.php'; // Include Parsedown for Markdown support

if (!isset($_GET['id'])) {
    die("Invalid post ID.");
}

$videoFileTypes = ['mp4', 'ogg', 'webm', 'mov'];
$audioFileTypes = ['mp3', 'wav', 'ogg', 'm4a'];


$postId = $_GET['id'];

// Handle Comment Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['comment_text'])) {
    if (!isset($_SESSION['user_id'])) {
        die("Error: You must be logged in to comment.");
    }

    $comment_text = trim($_POST['comment_text']);
    $parent_id = isset($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;
    $user_id = $_SESSION['user_id'];

    if (!empty($comment_text)) {
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, comment_text, parent_id, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$postId, $user_id, $comment_text, $parent_id]);

        // Redirect to refresh the page and display the new comment
        header("Location: post.php?id=" . $postId);
        exit;
    } else {
        echo "<p style='color: red;'>Comment cannot be empty.</p>";
    }
}

// Fetch Post Data
$stmt = $pdo->prepare("SELECT posts.*, users.username, users.profile_photo FROM posts JOIN users ON posts.user_id = users.id WHERE posts.id = ?");
$stmt->execute([$postId]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    die("Post not found.");
}

// Fetch Comments
$commentStmt = $pdo->prepare("SELECT comments.*, users.username, users.profile_photo FROM comments JOIN users ON comments.user_id = users.id WHERE comments.post_id = ? AND deleted_at IS NULL ORDER BY comments.created_at ASC");
$commentStmt->execute([$postId]);
$comments = $commentStmt->fetchAll(PDO::FETCH_ASSOC);

// Function to organize threaded comments
function buildCommentTree($comments, $parentId = null) {
    $tree = [];
    foreach ($comments as $comment) {
        if ($comment['parent_id'] == $parentId) {
            $comment['replies'] = buildCommentTree($comments, $comment['id']);
            $tree[] = $comment;
        }
    }
    return $tree;
}

$commentTree = buildCommentTree($comments);

$fileExtension = pathinfo($post['image_path']);
$isVideo = in_array(strtolower($fileExtension['extension']), $videoFileTypes);
$isAudio = in_array(strtolower($fileExtension['extension']), $audioFileTypes);
$isImage = !$isVideo && !$isAudio && !empty($post['image_path']);

$Parsedown = new Parsedown(); // Initialize Parsedown
$postContent = $Parsedown->text($post['content']); // Convert Markdown to HTML
?>
<head>
    <style>

        .profile-photo-post {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            object-fit: cover;
            margin-left: 1px; /* Space between username and profile picture */
            border: 2px solid black;
        }

        .post-user-link {
            display: flex;
            align-items: center;
            text-decoration: none; /* Remove underline */
            color: black; /* Make text black */
            font-style: normal; /* Ensure normal text style */
        }

        .comment {
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
            background: #f9f9f9;
            border-radius: 5px;
        }

        .replies {
            margin-left: 20px;
            border-left: 2px solid #ddd;
            padding-left: 10px;
            margin-top: 10px;
        }

    </style>
</head>
<div class="container">
    <h2><?php echo htmlspecialchars($post['title']); ?></h2>
    <p style="display: flex; align-items: center;" class="post-username">
                <a href="<?php echo 'user_profile.php?username=' . $post['username']?>" class="post-user-link">
                    <i>By <?php echo htmlspecialchars($post['username']);?></i>
                    <img src="<?php echo !empty($post['profile_photo']) ? htmlspecialchars($post['profile_photo']) : 'profile_photos/default_profile.png'; ?>" 
                        alt="Profile Photo" class="profile-photo-post">
                </a>
            </p>
    <p>
    <?php echo $postContent; ?></p> <!-- Render Markdown -->

    <?php if ($isVideo): ?>
        <video controls src="<?php echo htmlspecialchars($post['image_path']); ?>" style="max-width: 500px; max-height: 500px;" autoplay muted loop>
            Your browser does not support the video tag.
        </video>
    <?php endif; ?>

    <?php if ($isAudio): ?>
                <audio controls src="<?php echo htmlspecialchars($post['image_path']); ?>">
                    Your browser does not support the audio element.
                </audio>
    <?php endif; ?>

    <?php if ($isImage): ?>
        <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Post Image" style="max-width: 500px; max-height: 500px;">
    <?php endif; ?>



    <?php if (!empty($post['location_name']) && !empty($post['latitude']) && !empty($post['longitude'])): ?>
        <p>
            <strong>Location:</strong> 
            <a href="view_location.php?lat=<?php echo $post['latitude']; ?>&lng=<?php echo $post['longitude']; ?>">
                <?php echo htmlspecialchars($post['location_name']); ?>
            </a>
        </p>
    <?php endif; ?>

    <?php if (isset($_SESSION['user_id']) && $user && ($_SESSION['username'] == $post['username'] || $user['role'] == 'admin')): ?>
        <p class="post_controls">
            <a class="btn btn-warning" href="edit_post.php?id=<?php echo $post['id']; ?>">Edit</a>
            <a class="btn btn-danger" href="delete_post.php?id=<?php echo $post['id']; ?>" 
            onclick="return confirm('Are you sure you want to delete this post?');">Delete</a>
        </p>
    <?php endif; ?>

    <h3>Comments</h3>
    <!-- <?php foreach ($comments as $comment): ?>
        <p><strong><a href="<?php echo 'user_profile.php?username=' . $comment['username']?>" class="post-user-link">
                    <?php echo htmlspecialchars($comment['username']);?></i>
                    <img src="<?php echo !empty($comment['profile_photo']) ? htmlspecialchars($comment['profile_photo']) : 'profile_photos/default_profile.png'; ?>" 
                        alt="Profile Photo" class="profile-photo-post">
                </a></strong><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>
    <?php endforeach; ?> -->

    <div id="comments">
        <?php function renderComments($comments) {
            global $Parsedown;
            foreach ($comments as $comment): ?>
                <div class="comment" id="comment-<?php echo $comment['id']; ?>" style="margin-left: <?php echo $comment['parent_id'] ? '40px' : '0'; ?>;">
                    <p>
                        <strong><?php echo htmlspecialchars($comment['username']); ?></strong>
                        <img src="<?php echo !empty($comment['profile_photo']) ? htmlspecialchars($comment['profile_photo']) : 'profile_photos/default_profile.png'; ?>" 
                        alt="Profile Photo" class="profile-photo-post">
                        <small><?php echo $comment['created_at']; ?></small>
                    </p>
                    <p><?php echo $Parsedown->text($comment['comment_text']); ?></p>

                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $comment['user_id']): ?>
                        <button onclick="editComment(<?php echo $comment['id']; ?>)">Edit</button>
                        <button onclick="deleteComment(<?php echo $comment['id']; ?>)">Delete</button>
                    <?php endif; ?>

                    <button onclick="replyToComment(<?php echo $comment['id']; ?>)">Reply</button>

                    <div id="edit-form-<?php echo $comment['id']; ?>" style="display: none;">
                        <form method="post" action="edit_comment.php">
                            <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                            <textarea name="comment_text"><?php echo htmlspecialchars($comment['comment_text']); ?></textarea>
                            <button type="submit">Save</button>
                        </form>
                    </div>

                    <div id="reply-form-<?php echo $comment['id']; ?>" style="display: none;">
                        <form method="post">
                            <input type="hidden" name="parent_id" value="<?php echo $comment['id']; ?>">
                            <textarea name="comment_text" required></textarea>
                            <button type="submit">Reply</button>
                        </form>
                    </div>

                    <?php if (!empty($comment['replies'])): ?>
                        <div class="replies">
                            <?php renderComments($comment['replies']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach;
        }
        renderComments($commentTree);
        ?>

    </div>

    <!-- Add a Comment -->
    <?php if (isset($_SESSION['user_id'])): ?>
        <form method="post">
            <textarea name="comment_text" required></textarea>
            <button type="submit">Add Comment</button>
        </form>
    <?php else: ?>
        <p><a href="login.php">Login</a> to comment.</p>
    <?php endif; ?>
</div>


<script>
    function editComment(commentId) {
        document.getElementById("edit-form-" + commentId).style.display = "block";
    }

    function deleteComment(commentId) {
        if (confirm("Are you sure you want to delete this comment?")) {
            window.location.href = "delete_comment.php?id=" + commentId;
        }
    }

    function replyToComment(commentId) {
        document.getElementById("reply-form-" + commentId).style.display = "block";
    }
</script>

<?php include 'footer.php'; ?>
