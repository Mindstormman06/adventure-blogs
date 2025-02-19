<?php
include 'auth.php';
include 'config.php';

if (!isset($_GET['id'])) {
    die("Post ID missing.");
}

$post_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$post) {
    die("Post not found.");
}

$stmt1 = $pdo->prepare("SELECT tag_id FROM post_tags WHERE post_id = ?");
$stmt1->execute([$post_id]);
$tags = $stmt1->fetchAll(PDO::FETCH_ASSOC); // Fetch all associated tags

$stmt2 = $pdo->prepare("SELECT file_path FROM post_files WHERE post_id = ?");
$stmt2->execute([$post_id]);
$files = $stmt2->fetchAll(PDO::FETCH_COLUMN); // Fetch all associated files



// Ensure we found tags associated with this post
if ($tags) {
    echo '<pre>'; print_r($tags); echo '</pre>';
} else {
    echo "No tags associated with this post.";
}
echo '<pre>'; print_r($post); echo '</pre>';

// Check if the current user is the author of the post or an admin
if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $post['user_id']) || ($_SESSION['role'] == 'admin')) {

    // Step 1: Delete the references from post_tags
    $stmt1 = $pdo->prepare("DELETE FROM post_tags WHERE post_id = ?");
    $stmt1->execute([$post_id]);

    // Step 2: Delete tags if no other posts are referencing them
    foreach ($tags as $tag) {
        $stmt3 = $pdo->prepare("SELECT COUNT(*) FROM post_tags WHERE tag_id = ?");
        $stmt3->execute([$tag['tag_id']]);
        $tagCount = $stmt3->fetchColumn();

        // If no other posts are using this tag, delete the tag
        if ($tagCount == 0) {
            $stmt2 = $pdo->prepare("DELETE FROM tags WHERE id = ?");
            $stmt2->execute([$tag['tag_id']]);
        }
    }

    // Step 3: Delete the comments associated with the post
    $stmt4 = $pdo->prepare("DELETE FROM comments WHERE post_id = ?");
    $stmt4->execute([$post_id]);

    // Step 4: Delete the post itself
    $stmt5 = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    $stmt5->execute([$post_id]);

    // Step 5: Delete the media file if it exists

    foreach ($files as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }

    // Redirect to the homepage after deletion
    header("Location: index.php");
    exit;
} else {
    die("Access denied.");
}
?>
