<?php include 'auth.php'; ?>
<?php include 'header.php'; ?>
<?php include 'config.php'; ?>
<?php
// Check if user is an admin or user
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user['role'] !== 'user' && $user['role'] !== 'admin') {
    die("Access denied. Only registered users can create posts.");
}
?>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and trim the input data
    $title = trim($_POST["title"]);
    $content = trim($_POST["content"]);
    $user_id = $_SESSION["user_id"];
    $imagePath = null;

    // Backend Validation for Title and Content
    if (empty($title) || empty($content)) {
        echo "<p>Error: Both title and content must be filled in.</p>";
    } elseif (strlen($content) > 1000) {
        echo "<p>Error: Content exceeds the 1000-character limit.</p>";
    } else {
        // Handle Image Upload with file size restriction (max 50MB)
        if (!empty($_FILES["image"]["name"])) {
            // Define the max file size (50MB for example)
            $maxFileSize = 32 * 1024 * 1024; // 50MB in bytes

            // Check if the uploaded file is too large
            if ($_FILES["image"]["size"] > $maxFileSize) {
                echo "<p>Error: File is too large. Maximum size is 32 MB.</p>";
                exit; // Stop the script if the file is too large
            }

            // Move the uploaded file to the target directory
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
}
?>

<div class="container">
    <h2>Create a New Post</h2>
    <form method="post" enctype="multipart/form-data" onsubmit="return validatePost()">
        <!-- Title input field -->
        <label>Title:</label>
        <input type="text" name="title" id="title" required>
        
        <!-- Content textarea with max length -->
        <label>Content:</label>
        <textarea name="content" id="content" required maxlength="1000"></textarea>
        <p id="content-char-count">0/1000 characters used</p>

        <p><i>Markdown is supported! Click <a href="https://github.com/adam-p/markdown-here/wiki/markdown-cheatsheet">here</a> for a guide on using Markdown</i></p>

        <!-- Image upload -->
        <label>Upload Media:</label>
        <input type="file" name="image" required>
        
        <!-- Tags input field -->
        <label>Tags (comma-separated):</label>
        <input type="text" name="tags">
        
        <!-- Submit button -->
        <button type="submit">Post</button>
    </form>
</div>

<!-- JavaScript for frontend validation -->
<script>
    // Real-time content length display
    document.getElementById("content").addEventListener("input", function() {
        var charCount = this.value.length;
        document.getElementById("content-char-count").textContent = charCount + "/1000 characters used";
    });

    // Frontend validation before submitting
    function validatePost() {
        var title = document.getElementById("title").value;
        var content = document.getElementById("content").value;
        
        // Ensure title and content are not empty
        if (title.trim() === "" || content.trim() === "") {
            alert("Both the title and content must be filled in.");
            return false; // Prevent form submission
        }

        // Check for character limit in content
        if (content.length > 1000) {
            alert("Content exceeds the 1000-character limit.");
            return false; // Prevent form submission
        }

        return true; // Allow form submission
    }
</script>

<?php include 'footer.php'; ?>
