<?php
include 'config.php';

if (!isset($_GET['token'])) {
    die("Invalid request.");
}

$token = $_GET['token'];

// Find user with this token
$stmt = $pdo->prepare("SELECT id, pending_email FROM users WHERE verification_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || empty($user['pending_email'])) {
    die("Invalid or expired token.");
}

// Update email and clear verification fields
$stmt = $pdo->prepare("UPDATE users SET email = ?, pending_email = NULL, verification_token = NULL WHERE id = ?");
$stmt->execute([$user['pending_email'], $user['id']]);

echo "Email successfully updated!";
