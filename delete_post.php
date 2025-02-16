<?php
include 'auth.php';
include 'config.php';

if (!isset($_GET['id'])) {
    die("Post ID missing.");
}

$post_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$post) {
    die("Post not found.");
}
echo '<pre>'; print_r($post); echo '</pre>';


$stmt1 = $pdo->prepare("SELECT * FROM post_tags WHERE post_id = ?");
$stmt1->execute([$post_id]);
$tag_id = $stmt1->fetch(PDO::FETCH_ASSOC);

echo '<pre>'; print_r($tag_id); echo '</pre>';
echo $tag_id['tag_id'];


$stmt2 = $pdo->prepare("SELECT post_id FROM comments WHERE post_id = ?");
$stmt2->execute([$post_id]);

$stmt3 = $pdo->prepare("SELECT id FROM tags WHERE id = ?");
$stmt3->execute([$tag_id]);

// echo '<pre>'; print_r($_SESSION); echo '</pre>';
if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $post['user_id']) || ($_SESSION['role'] == 'admin')) {

    // Delete the tags associated with the post
    $stmt1 = $pdo->prepare("DELETE FROM post_tags WHERE post_id = ?");
    $stmt1->execute([$post_id]);

    // Delete the tags associated with the post
    $stmt3 = $pdo->prepare("DELETE FROM tags WHERE id = ?");
    $stmt3->execute([$tag_id['tag_id']]);

    // Delete the comments associated with the post
    $stmt2 = $pdo->prepare("DELETE FROM comments WHERE post_id = ?");
    $stmt2->execute([$post_id]);

    // Delete the post itself
    $stmt3 = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    $stmt3->execute([$post_id]);

    // Redirect to the homepage after deletion
    header("Location: index.php");
    exit;
} else {
    die("Access denied.");
}

// if (!$post || ($post['user_id'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin')) {
//     die("Access denied.");
// }

// $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
// $stmt->execute([$post_id]);

// header("Location: index.php");
?>
