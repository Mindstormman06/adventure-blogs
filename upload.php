<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'config.php';

if (!isset($_SESSION['user_id'])) {
    die("Access denied");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['image'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $image_path = 'uploads/' . basename($_FILES['image']['name']);

    if (move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content, image_path) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$_SESSION['user_id'], $title, $content, $image_path])) {
            echo "Post uploaded!";
        } else {
            echo "Error uploading post.";
        }
    }
}
?>
<form method="post" enctype="multipart/form-data">
    Title: <input type="text" name="title" required>
    Content: <textarea name="content"></textarea>
    Image: <input type="file" name="image">
    <input type="submit" value="Upload">
</form>