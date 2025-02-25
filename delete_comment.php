<?php
include 'auth.php';
include 'config.php';

if (!isset($_GET['id'])) {
    die("Invalid comment ID.");
}

$comment_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM comments WHERE id = ? AND user_id = ?");
$stmt->execute([$comment_id, $user_id]);

if ($stmt->rowCount() == 0) {
    die("You do not have permission to delete this comment.");
}

$stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
$stmt->execute([$comment_id]);

header("Location: " . $_SERVER['HTTP_REFERER']);
