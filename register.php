<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Make sure PHPMailer is loaded

include 'header.php';
include 'config.php';
require 'models/User.php'; // Include the User class
$emailConfig = require 'email_config_personal.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $email = trim($_POST["email"]);

    if (!empty($username) && !empty($email) && !empty($password)) {
        $userObj = new User($pdo);

        try {
            $verificationToken = $userObj->register($username, $email, $password);
            $userObj->sendVerificationEmail($email, $verificationToken, $emailConfig);
            echo "<p>Registration successful! Please check your email to verify your account (Check your junk!). If you do not verify within a day, you will have to re-create your account.</p>";
        } catch (Exception $e) {
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