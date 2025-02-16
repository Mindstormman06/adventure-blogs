<?php include 'auth.php'; ?>
<?php include 'header.php'; ?>
<?php include 'config.php'; ?>
<?php
include 'auth.php';
include 'config.php';

// Check if user is an admin
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user['role'] !== 'user' && $user['role'] !== 'admin') {
    die("Access denied. Only registered users can create posts.");
}
?>


<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST["title"]);
    $content = trim($_POST["content"]);
    $user_id = $_SESSION["user_id"];
    $imagePath = null;

    // Handle Image Upload
    if (!empty($_FILES["image"]["name"])) {
        $targetDir = "uploads/";
        $imagePath = $targetDir . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $imagePath);
    }

    // Insert Post into Database
    $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content, image_path) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $title, $content, $imagePath]);

    // Get Post ID
    $postId = $pdo->lastInsertId();

    // Insert Tags
    if (!empty($_POST["tags"])) {
        $tags = explode(",", $_POST["tags"]);
        foreach ($tags as $tag) {
            $tag = trim($tag);
            if ($tag) {
                $stmt = $pdo->prepare("INSERT INTO tags (name) VALUES (?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
                $stmt->execute([$tag]);

                $tagId = $pdo->lastInsertId();
                $stmt = $pdo->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)");
                $stmt->execute([$postId, $tagId]);
            }
        }
    }

    echo "<p>Post uploaded successfully! <a href='index.php'>View posts</a></p>";
    header("Location: index.php");
}
?>

<div class="container">
    <h2>Create a New Post</h2>
    <form method="post" enctype="multipart/form-data">
        <label>Title:</label>
        <input type="text" name="title" required>
        
        <label>Content:</label>
        <textarea name="content" required></textarea>
        
        <label>Upload Media:</label>
        <input type="file" name="image">
        
        <label>Tags (comma-separated):</label>
        <input type="text" name="tags">
        
        <button type="submit">Post</button>
    </form>
</div>

<?php include 'footer.php'; ?>
