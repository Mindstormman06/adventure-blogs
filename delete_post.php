<?php
include 'auth.php';
include 'config.php';
require 'models/Post.php'; // Include the Post class

if (!isset($_GET['id'])) {
    die("Post ID missing.");
}

$post_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    $postObj = new Post($pdo, new Parsedown(), new HTMLPurifier(HTMLPurifier_Config::createDefault()));
    $postObj->deletePost($post_id, $user_id);
} catch (Exception $e) {
    die($e->getMessage());
}

// Redirect to the homepage after deletion
header("Location: index.php");
exit;
