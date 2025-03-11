<?php
include 'config.php';
require 'vendor/autoload.php'; // Load PHPMailer (install with Composer if needed)
require 'models/User.php'; // Include the User class

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$emailConfig = require 'email_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

$userObj = new User($pdo);

// Fetch user data
$user = $userObj->getUserById($_SESSION['user_id']);

if (!$user) {
    die("User not found.");
}

// Get form inputs
$currentPassword = $_POST['current_password'] ?? '';
$newUsername = $_POST['username'] ?? $user['username'];
$newEmail = $_POST['email'] ?? $user['email'];
$newPassword = $_POST['password'] ?? '';
$instagram = $_POST['instagram_link'] ?? '';
$website = $_POST['website_link'] ?? '';

if (!password_verify($currentPassword, $user['password_hash'])) {
    die("Incorrect current password.");
}

// Handle profile photo upload
$profilePhoto = $user['profile_photo']; // Keep the old photo by default
if (!empty($_FILES['profile_photo']['name'])) {
    $uploadDir = "profile_photos/";
    $fileName = basename($_FILES["profile_photo"]["name"]);
    $targetFile = $uploadDir . time() . "_" . $fileName;
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // Validate image file type
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    if (in_array($imageFileType, $allowedTypes)) {
        if (move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $targetFile)) {
            $profilePhoto = $targetFile;
        } else {
            die("Error uploading profile photo.");
        }
    } else {
        die("Invalid file type. Only JPG, PNG, and GIF are allowed.");
    }
}

// Check if the email has changed
if ($newEmail !== $user['email']) {
    // Generate verification token
    $token = bin2hex(random_bytes(16));

    // Store the pending email and token
    $userObj->updatePendingEmail($user['id'], $newEmail, $token);

    // Send verification email
    try {
        $userObj->sendEmailChangeVerification($newEmail, $token, $emailConfig);
        echo "A verification email has been sent. Please check your inbox.";
    } catch (Exception $e) {
        echo "Email could not be sent. Mailer Error: {$e->getMessage()}";
    }
}

// Update database with new profile information
if (!empty($newPassword)) {
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $userObj->updateUserProfile($user['id'], $newUsername, $newEmail, $hashedPassword, $instagram, $website, $profilePhoto);
} else {
    $userObj->updateUserProfile($user['id'], $newUsername, $newEmail, $user['password_hash'], $instagram, $website, $profilePhoto);
}

// Redirect with a message
if ($newEmail !== $user['email']) {
    $_SESSION['email_verification_notice'] = "Please verify your new email address. A verification link has been sent.";
    header("Location: edit_user.php");
    exit();
} else {
    header("Location: edit_user.php?success=Your profile has been updated.");
    exit();
}
