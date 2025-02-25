<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Make sure PHPMailer is loaded

include 'header.php';
include 'config.php';
$emailConfig = require 'email_config_personal.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $email = trim($_POST["email"]);

    if (!empty($username) && !empty($email) && !empty($password)) {
        // Check if the email already exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            echo "<p>Error: Email already in use.</p>";
            exit;
        }

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Generate a unique verification token
        $verificationToken = bin2hex(random_bytes(16));

        // Insert user data into the database
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, verification_token, role, verified) VALUES (?, ?, ?, ?, 'user', 0)");
        try {
            $stmt->execute([$username, $email, $hashedPassword, $verificationToken]);

            // Send verification email using PHPMailer
            $verificationLink = "http://adventure-blog.ddns.net/verify_email.php?token=$verificationToken";

            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = $emailConfig['smtp_host'];
                $mail->SMTPAuth = true;
                $mail->Username = $emailConfig['smtp_username']; // Your Gmail email address
                $mail->Password = $emailConfig['smtp_password']; // The 16-character app password you generated
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = $emailConfig['smtp_port'];

                $mail->setFrom($emailConfig['from_email'], $emailConfig['from_name']);
                $mail->addAddress($email); // Recipient's email address

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Email Verification';
                $mail->Body    = 'Please click the link to verify your email: <a href="' . $verificationLink . '">Verify Email</a>';

                // Send the email
                $mail->send();
                echo "<p>Registration successful! Please check your email to verify your account (Check your junk!). If you do not verify within a day, you will have to re-create your account.</p>";
            } catch (Exception $e) {
                echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        } catch (PDOException $e) {
            echo "<p>Error: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>Please fill in all fields.</p>";
    }
}
?>

<div class="container">
    <h2>Register</h2>
    <form method="post">
        <label>Email:</label>
        <input type="email" name="email" required>
        <br>
        <label>Username:</label>
        <input type="text" name="username" required>
        <br>
        <label>Password:</label>
        <input type="password" name="password" required>
        <br>
        <button type="submit" class="btn btn-primary">Register</button>
    </form>
</div>

<?php include 'footer.php'; ?>