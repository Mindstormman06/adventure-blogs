<?php
include 'auth.php';
include 'config.php';
include 'header.php';

if (!isset($_GET['id'])) {
    die("Post ID missing.");
}

$post_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post || ($post['user_id'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin')) {
    die("Access denied.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST["title"]);
    $content = trim($_POST["content"]);

    $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ? WHERE id = ?");
    $stmt->execute([$title, $content, $post_id]);

    header("Location: index.php");
}
?>

<div class="container">
    <h2>Edit Post</h2>
    <form method="post">
        <div class="form-group">
            <label>Title:</label>
            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($post['title']); ?>" required>
        </div>

        <div class="form-group">
            <label>Content:</label>
            <textarea name="content" class="form-control" rows="5" required><?php echo htmlspecialchars($post['content']); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="index.php" class="btn btn-secondary">Back to Home</a>
    </form>
</div>

<?php include 'footer.php'; ?>