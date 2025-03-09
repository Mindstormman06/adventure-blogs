<?php
include 'auth.php';
include 'config.php';
require 'models/Post.php'; // Include the Post class

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['comment_id']) && isset($_POST['comment_text'])) {
    $comment_id = $_POST['comment_id'];
    $comment_text = trim($_POST['comment_text']);
    $user_id = $_SESSION['user_id'];

    if (!empty($comment_text)) {
        $postObj = new Post($pdo, new Parsedown(), new HTMLPurifier(HTMLPurifier_Config::createDefault()));
        $postObj->editComment($comment_id, $user_id, $comment_text);
    }
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
