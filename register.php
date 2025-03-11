<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Make sure PHPMailer is loaded

include 'header.php';
include 'config.php';
require 'models/User.php'; // Include the User class
$emailConfig = require 'email_config_personal.php';

$error = '';
$errorClass = 'error-box';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $email = trim($_POST["email"]);
    $errorClass = 'error-box';

    if (!empty($username) && !empty($email) && !empty($password)) {
        $userObj = new User($pdo);

        $result = $userObj->register($username, $email, $password);

        if (isset($result['success']) && $result['success'] === true) {
            try {
                $userObj->sendVerificationEmail($email, $result['token'], $emailConfig);
                $error = "Registration successful! Please check your email to verify your account (Check your junk!). If you do not verify within a day, you will have to re-create your account.";
                $errorClass = 'success-box';

            } catch (Exception $e) {
                $error = "Error: " . $e->getMessage();
            }
        } else {
            $error = $result; // Handle errors properly
        }        
    } else {
        $error = "Please fill in all fields.";
    }
}
?>

<div class="container">
    <h2>Register</h2>
    <?php if (!empty($error)): ?>
        <div class="<?php echo $errorClass; ?>"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
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