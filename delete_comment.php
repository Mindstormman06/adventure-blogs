<?php
include 'auth.php';
include 'config.php';
require 'models/Post.php'; // Include the Post class

if (!isset($_GET['id'])) {
    die("Invalid comment ID.");
}

$comment_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    $postObj = new Post($pdo, new Parsedown(), new HTMLPurifier(HTMLPurifier_Config::createDefault()));
    $postObj->deleteComment($comment_id, $user_id);
} catch (Exception $e) {
    die($e->getMessage());
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
