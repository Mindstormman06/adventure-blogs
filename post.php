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

// Approved file types for audio and video
$videoFileTypes = ['mp4', 'ogg', 'webm', 'mov'];
$audioFileTypes = ['mp3', 'wav', 'ogg', 'm4a', 'flac'];


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

$stmt1 = $pdo->query("
    SELECT tags.id, tags.name, post_tags.post_id
    FROM tags
    INNER JOIN post_tags ON tags.id = post_tags.tag_id");
$tags1 = $stmt1->fetchAll();

$postFilesStmt = $pdo->query("SELECT post_id, file_path, original_filename FROM post_files");
$postFiles = [];
while ($row = $postFilesStmt->fetch(PDO::FETCH_ASSOC)) {
    $postFiles[$row['post_id']][] = $row['file_path'];
    $postFilesOriginal[$row['post_id']][] = $row['original_filename'];
}

// Fetch Comments
$commentStmt = $pdo->prepare("SELECT comments.*, users.username, users.profile_photo FROM comments JOIN users ON comments.user_id = users.id WHERE comments.post_id = ? AND deleted_at IS NULL ORDER BY comments.created_at ASC");
$commentStmt->execute([$postId]);
$comments = $commentStmt->fetchAll(PDO::FETCH_ASSOC);

// Function to organize threaded comments
function buildCommentTree($comments, $parentId = null)
{
    $tree = [];
    foreach ($comments as $comment) {
        if ($comment['parent_id'] == $parentId) {
            // Only build replies for base-level comments
            if ($parentId === null) {
                $comment['replies'] = array_filter($comments, function ($reply) use ($comment) {
                    return $reply['parent_id'] == $comment['id'];
                });
            } else {
                $comment['replies'] = [];
            }
            $tree[] = $comment;
        }
    }
    return $tree;
}

$commentTree = buildCommentTree($comments);


$commentTree = buildCommentTree($comments);

$fileExtension = pathinfo($postFiles[$post['id']][0]);
$isVideo = in_array(strtolower($fileExtension['extension']), $videoFileTypes);
$isAudio = in_array(strtolower($fileExtension['extension']), $audioFileTypes);
$isImage = !$isVideo && !$isAudio && !empty($post['image_path']);
$i = -1;

$Parsedown = new Parsedown(); // Initialize Parsedown
$postContent = $Parsedown->text($post['content']); // Convert Markdown to HTML
?>

<div class="container">
    <h2><?php echo htmlspecialchars($post['title']); ?></h2>
    <p style="display: flex; align-items: center;" class="post-username">
        <a href="<?php echo 'user_profile.php?username=' . $post['username'] ?>" class="post-user-link link-primary">
            <?php echo htmlspecialchars($post['username']); ?>
            <img src="<?php echo !empty($post['profile_photo']) ? htmlspecialchars($post['profile_photo']) : 'profile_photos/default_profile.png'; ?>"
                alt="Profile Photo" class="profile-photo-post">
        </a>
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

    <?php if (!empty($post['location_name']) && !empty($post['latitude']) && !empty($post['longitude'])): ?>
        <p>
            <strong>Location:</strong>
            <a href="view_location.php?lat=<?php echo $post['latitude']; ?>&lng=<?php echo $post['longitude']; ?>&name=<?php echo $post['location_name']; ?>">
                <?php echo htmlspecialchars($post['location_name']); ?>
            </a>
        </p>
    <?php endif; ?>
    <!-- Render Markdown -->
    <p><?php echo $postContent; ?></p>


    <?php if (isset($postFiles[$post['id']]) && is_array($postFiles[$post['id']])): ?>
        <?php foreach ($postFiles[$post['id']] as $file): ?>
            <?php
            $fileExtension = pathinfo($file, PATHINFO_EXTENSION);
            $isVideo = in_array(strtolower($fileExtension), $videoFileTypes);
            $isAudio = in_array(strtolower($fileExtension), $audioFileTypes);
            $isImage = !$isVideo && !$isAudio;
            ?>

            <?php if ($isVideo): ?>
                <video style="max-width: 500px; max-height: 500px;" controls src="<?php echo htmlspecialchars($file); ?>" autoplay muted loop>
                    Your browser does not support the video tag.
                </video>
            <?php endif; ?>

            <?php if ($isAudio): ?>
                <div>
                    <?php
                    $i += 1;
                    $originalName = $postFilesOriginal[$post['id']][$i];
                    $displayName = mb_strimwidth($originalName, 0, 40, "...");
                    ?>
                    <p style="margin-top: 15px"><?php echo htmlspecialchars($displayName); ?></p>
                    <audio controls src="<?php echo htmlspecialchars($file); ?>" loop></audio>
                </div>
            <?php endif; ?>

            <?php if ($isImage): ?>
                <img src="<?php echo htmlspecialchars($file); ?>" alt="Failed to load image" style="max-width: 65%; max-height: 65%;" class="post-image">
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>


    <?php if (isset($_SESSION['user_id']) && $user && ($_SESSION['username'] == $post['username'] || $user['role'] == 'admin')): ?>
        <p class="post_controls">
            <a class="btn btn-warning" href="edit_post.php?id=<?php echo $post['id']; ?>">✏️</a>
            <a class="btn btn-danger" href="delete_post.php?id=<?php echo $post['id']; ?>"
                onclick="return confirm('Are you sure you want to delete this post?');">🗑️</a>
        </p>
    <?php endif; ?>

    <h3>Comments</h3>
    <!-- <?php foreach ($comments as $comment): ?>
        <p><strong><a href="<?php echo 'user_profile.php?username=' . $comment['username'] ?>" class="post-user-link">
                    <?php echo htmlspecialchars($comment['username']); ?></i>
                    <img src="<?php echo !empty($comment['profile_photo']) ? htmlspecialchars($comment['profile_photo']) : 'profile_photos/default_profile.png'; ?>" 
                        alt="Profile Photo" class="profile-photo-post">
                </a></strong><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>
    <?php endforeach; ?> -->

    <div id="comments">
        <?php function renderComments($comments)
        {
            global $Parsedown;
            foreach ($comments as $comment): ?>
                <div class="comment" id="comment-<?php echo $comment['id']; ?>">
                    <p>
                        <a href="<?php echo 'user_profile.php?username=' . $comment['username'] ?>" class="post-user-link link-primary">
                            <strong><?php echo htmlspecialchars($comment['username']); ?></strong>
                            <img src="<?php echo !empty($comment['profile_photo']) ? htmlspecialchars($comment['profile_photo']) : 'profile_photos/default_profile.png'; ?>"
                                alt="Profile Photo" class="profile-photo-post">
                        </a>
                        <small><?php echo $comment['created_at']; ?></small>
                    </p>
                    <p><?php echo $Parsedown->text($comment['comment_text']); ?></p>

                    <?php if ($comment['parent_id'] == null): ?>
                        <button onclick="replyToComment(<?php echo $comment['id']; ?>)"
                            class="text-white bg-green-700 hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800">
                            Reply
                        </button>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $comment['user_id']): ?>
                        <button onclick="editComment(<?php echo $comment['id']; ?>)"
                            class="text-black bg-yellow-400 hover:bg-yellow-500 focus:outline-none focus:ring-4 focus:ring-yellow-300 font-medium rounded-full text-sm px-5 py-2.5 text-center dark:focus:ring-yellow-900">
                            ✏️
                        </button>
                        <button onclick="deleteComment(<?php echo $comment['id']; ?>)"
                            class="text-white bg-red-700 hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 font-medium rounded-full text-sm px-5 py-2.5 text-center dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900">
                            🗑️
                        </button>
                    <?php endif; ?>

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
                            <button type="submit"
                                class="text-white bg-green-700 hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800">
                                Post Reply</button>
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
            <button type="submit" class="btn btn-primary">Post Comment</button>
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