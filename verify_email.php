<?php
include 'config.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Check if the token exists and is valid
    $stmt = $pdo->prepare("SELECT * FROM users WHERE verification_token = ? AND verified = 0");
    $stmt->execute([$token]);

    if ($stmt->rowCount() > 0) {
        // Token is valid, activate the user
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Update the user to 'verified' status
        $stmt = $pdo->prepare("UPDATE users SET verified = 1, verification_token = NULL WHERE id = ?");
        $stmt->execute([$user['id']]);

        echo "<p>Your email has been verified. You can now <a href='login.php'>login</a>.</p>";
    } else {
        echo "<p>Invalid or expired verification link.</p>";
    }
} else {
    echo "<p>No verification token provided.</p>";
}
?>
