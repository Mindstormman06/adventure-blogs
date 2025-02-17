<?php
include 'auth.php';
include 'config.php';

if (!isset($_GET['id'])) {
    die("Invalid comment ID.");
}

$comment_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Soft delete (mark as deleted)
$stmt = $pdo->prepare("UPDATE comments SET deleted_at = NOW() WHERE id = ? AND user_id = ?");
$stmt->execute([$comment_id, $user_id]);

header("Location: " . $_SERVER['HTTP_REFERER']);
?>
