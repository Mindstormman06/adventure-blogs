<?php
include 'config.php';
require 'vendor/autoload.php'; // Load PHPMailer (install with Composer if needed)

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$emailConfig = require 'email_config_personal.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

// Fetch user data
$stmt = $pdo->prepare("SELECT id, username, email, password_hash, profile_photo, instagram_link, website_link FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

// Get form inputs
$currentPassword = $_POST['current_password'] ?? '';
$newUsername = $_POST['username'] ?? $user['username'];
$newEmail = $_POST['email'] ?? $user['email'];
$newPassword = $_POST['password_hash'] ?? '';
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
    $stmt = $pdo->prepare("UPDATE users SET pending_email = ?, verification_token = ? WHERE id = ?");
    $stmt->execute([$newEmail, $token, $user['id']]);

    // Send verification email
    $verificationLink = "http://adventure-blog.ddns.net/verify_email_change.php?token=$token";
    
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $emailConfig['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $emailConfig['smtp_username'];
        $mail->Password = $emailConfig['smtp_password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $emailConfig['smtp_port'];
        
        $mail->setFrom($emailConfig['from_email'], $emailConfig['from_name']);
        $mail->addAddress($newEmail);
        $mail->Subject = "Verify Your New Email Address";
        $mail->Body = "Click the link to verify your new email: $verificationLink";

        $mail->send();
        echo "A verification email has been sent. Please check your inbox.";
    } catch (Exception $e) {
        echo "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

// Update database with new profile information
if (!empty($newPassword)) {
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET username = ?, password_hash = ?, profile_photo = ?, instagram_link = ?, _link = ? WHERE id = ?");
    $stmt->execute([$newUsername, $hashedPassword, $profilePhoto, $instagram, $website, $user['id']]);
} else {
    $stmt = $pdo->prepare("UPDATE users SET username = ?, profile_photo = ?, instagram_link = ?, website_link = ? WHERE id = ?");
    $stmt->execute([$newUsername, $profilePhoto, $instagram, $website, $user['id']]);
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
?>
