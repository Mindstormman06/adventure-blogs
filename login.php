<?php include 'header.php'; ?>
<?php include 'config.php'; ?>

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['verified'] == 0) {
            echo "<p>Your account is not verified. Please check your email for the verification link.</p> <a href='login.php'>Back to login</a>";
            exit;
        } elseif ($user['verified'] == 1) {
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $username;
        $_SESSION["role"] = $user["role"];
        header("Location: index.php");
        exit;
        }
    } else {
        echo "<p>Invalid username or password.</p>";
    }
}
?>

<h2>Login</h2>
<form method="post">
    <label>Username:</label>
    <input type="text" name="username" required>
    <br>
    <label>Password:</label>
    <input type="password" name="password" required>
    <br>
    <button type="submit" class="btn btn-primary">Login</button>
</form>

<?php include 'footer.php'; ?>
