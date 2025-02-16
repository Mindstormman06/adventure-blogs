<?php 
include 'header.php'; 
include 'config.php'; 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_GET['id'])) {
    die("Invalid post ID.");
}

$videoFileTypes = ['mp4', 'ogg', 'webm', 'mov'];


$postId = $_GET['id'];

// Handle Comment Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['comment_text'])) {
    if (!isset($_SESSION['user_id'])) {
        die("Error: You must be logged in to comment.");
    }

    $comment_text = trim($_POST['comment_text']);
    $user_id = $_SESSION['user_id'];

    if (!empty($comment_text)) {
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, comment_text, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$postId, $user_id, $comment_text]);

        // Redirect to refresh the page and display the new comment
        header("Location: post.php?id=" . $postId);
        exit;
    } else {
        echo "<p style='color: red;'>Comment cannot be empty.</p>";
    }
}

// Fetch Post Data
$stmt = $pdo->prepare("SELECT posts.*, users.username FROM posts JOIN users ON posts.user_id = users.id WHERE posts.id = ?");
$stmt->execute([$postId]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    die("Post not found.");
}

// Fetch Comments
$commentStmt = $pdo->prepare("SELECT comments.comment_text, users.username FROM comments JOIN users ON comments.user_id = users.id WHERE comments.post_id = ? ORDER BY comments.created_at ASC");
$commentStmt->execute([$postId]);
$comments = $commentStmt->fetchAll(PDO::FETCH_ASSOC);

$fileExtension = pathinfo($post['image_path']);
$isVideo = in_array(strtolower($fileExtension['extension']), $videoFileTypes);
$isImage = !$isVideo && !empty($post['image_path']); // Ensure it's not empty and not a video
?>

<div class="container">
    <h2><?php echo htmlspecialchars($post['title']); ?></h2>
    <p><i>By <?php echo htmlspecialchars($post['username']); ?></i></p>
    <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>

    <?php if ($isVideo): ?>
        <video controls src="<?php echo htmlspecialchars($post['image_path']); ?>" style="max-width: 500px; max-height: 500px;" autoplay muted loop>
            Your browser does not support the video tag.
        </video>
    <?php endif; ?>

    <?php if ($isImage): ?>
        <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Post Image" style="max-width: 500px; max-height: 500px;">
    <?php endif; ?>

    <h3>Comments</h3>
    <?php foreach ($comments as $comment): ?>
        <p><strong><?php echo htmlspecialchars($comment['username']); ?>:</strong> <?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>
    <?php endforeach; ?>

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

<?php include 'footer.php'; ?>
