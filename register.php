<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Make sure PHPMailer is loaded

include 'header.php';
include 'config.php';
require 'models/User.php'; // Include the User class
$emailConfig = require 'email_config.php';

$error = '';
$errorClass = 'error-box';

$ip     = $_SERVER['REMOTE_ADDR']; // means we got user's IP address 
$json   = file_get_contents( 'http://ip-api.com/json/' . $ip); // this one service we gonna use to obtain timezone by IP
// maybe it's good to add some checks (if/else you've got an answer and if json could be decoded, etc.)
$ipData = json_decode( $json, true);

if ($ipData['timezone']) {
    $tz = new DateTimeZone( $ipData['timezone']);
    $now = new DateTime( 'now', $tz); // DateTime object corellated to user's timezone
} else {
   echo 'Failed to get timezone';
}

// Calculate the offset from UTC
$utc = new DateTime('now', new DateTimeZone('UTC'));
$offset = $now->getOffset() / 3600; // Offset in hours

// Calculate the next midnight in PST
$eventTimePST = new DateTime('now', new DateTimeZone('America/Los_Angeles'));
$eventTimePST->setTime(0, 0, 0);
if ($eventTimePST < new DateTime('now', new DateTimeZone('America/Los_Angeles'))) {
    $eventTimePST->modify('+1 day');
}
$eventTimePST->setTimezone($tz); // Convert to user's timezone
$eventTimeLocal = $eventTimePST->format('Y-m-d H:i:s');

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
                $error = "Registration successful! Please check your email to verify your account (Check your junk!). If you do not verify before " . $eventTimeLocal . ", you will have to re-create your account.";
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