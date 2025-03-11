<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Ensure PHPMailer is loaded

class User {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function register($username, $email, $password) {
        // Check if the email already exists
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            return "Email already in use.";
        }

        // Check if the username already exists
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            return "Username already in use.";
        }

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Generate a unique verification token
        $verificationToken = bin2hex(random_bytes(16));

        // Insert user data into the database
        $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password_hash, verification_token, role, verified) VALUES (?, ?, ?, ?, 'user', 0)");
        $stmt->execute([$username, $email, $hashedPassword, $verificationToken]);

        return [
            'success' => true,
            'token' => $verificationToken
        ];
    }

    public function sendVerificationEmail($email, $verificationToken, $emailConfig) {
        $verificationLink = "http://adventure-blog.ddns.net/verify_email.php?token=$verificationToken";

        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $emailConfig['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $emailConfig['smtp_username'];
            $mail->Password = $emailConfig['smtp_password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $emailConfig['smtp_port'];

            $mail->setFrom($emailConfig['from_email'], $emailConfig['from_name']);
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Email Verification';
            $mail->Body    = 'Please click the link to verify your email: <a href="' . $verificationLink . '">Verify Email</a>';

            // Send the email
            $mail->send();
        } catch (Exception $e) {
            throw new Exception("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }
    }

    public function sendEmailChangeVerification($newEmail, $token, $emailConfig) {
        $verificationLink = "http://adventure-blog.ddns.net/verify_email_change.php?token=$token";

        $mail = new PHPMailer(true);
        try {
            // Server settings
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
        } catch (Exception $e) {
            throw new Exception("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }
    }

    public function login($username, $password) {
        // Check if the user exists
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if the user exists and the password is correct
        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['verified'] == 0) {
                return "Your account is not verified. Please check your email for the verification link.";
            }

            return $user;
        } else {
            return "Invalid username or password.";
        }
    }

    public function rememberUser($userId) {
        $token = bin2hex(random_bytes(32));
        $expiry = time() + 60 * 60 * 24 * 30; // 30 days

        $stmt = $this->pdo->prepare("UPDATE users set remember_token = ? WHERE id = ?");
        $stmt->execute([$token, $userId]);

        setcookie("remember_token", $token, $expiry, "/", "", false, true);
    }

    public function logout() {
        session_destroy();
        setcookie("remember_token", "", time() - 3600, "/", "", false, true);
    }

    public function authenticate() {
        if (!isset($_SESSION["user_id"]) && isset($_COOKIE["remember_token"])) {
            $token = $_COOKIE["remember_token"];

            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE remember_token = ?");
            $stmt->execute([$token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["role"] = $user["role"];
            } else {
                setcookie("remember_token", "", time() - 3600, "/", "", true, true);
                header("Location: login.php");
                exit;
            }
        }
    }

    public function getUserById($userId) {
        $stmt = $this->pdo->prepare("SELECT id, username, email, password_hash, instagram_link, website_link FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateUserProfile($userId, $username, $email, $passwordHash, $instagramLink, $websiteLink, $profilePhotoPath = null) {
        $stmt = $this->pdo->prepare("UPDATE users SET username = ?, email = ?, password_hash = ?, instagram_link = ?, website_link = ?, profile_photo = ? WHERE id = ?");
        $stmt->execute([$username, $email, $passwordHash, $instagramLink, $websiteLink, $profilePhotoPath, $userId]);
    }

    public function updatePendingEmail($userId, $newEmail, $token) {
        $stmt = $this->pdo->prepare("UPDATE users SET pending_email = ?, verification_token = ? WHERE id = ?");
        $stmt->execute([$newEmail, $token, $userId]);
    }
}