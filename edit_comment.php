<?php
include 'auth.php';
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['comment_id']) && isset($_POST['comment_text'])) {
    $comment_id = $_POST['comment_id'];
    $comment_text = trim($_POST['comment_text']);
    $user_id = $_SESSION['user_id'];

    if (!empty($comment_text)) {
        $stmt = $pdo->prepare("UPDATE comments SET comment_text = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->execute([$comment_text, $comment_id, $user_id]);
    }
}

header("Location: " . $_SERVER['HTTP_REFERER']);
