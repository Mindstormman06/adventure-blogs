<?php include 'header.php'; ?>
<?php include 'config.php'; ?>
<?php require 'models/User.php'; // Include the User class ?>

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $remember = isset($_POST["remember_me"]);

    $userObj = new User($pdo);

    $result = $userObj->login($username, $password);

    if (is_array($result)) {

        // Set the session variables
        $_SESSION["user_id"] = $result["id"];
        $_SESSION["username"] = $result["username"];
        $_SESSION["role"] = $result["role"];
        if ($remember) {
            $userObj->rememberUser($result["id"]);
        }
        header("Location: index.php");
        exit;
    } else {
        $error = $result;
    }
}
?>

<div class="container">
    <h2>Adventure Blogs</h2>
    <?php if (!empty($error)): ?>
        <div class="error-box"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post">
        <label>Username:</label>
        <input type="text" name="username" required>
        <br>
        <label>Password:</label>
        <input type="password" name="password" required>
        <input class="form-check-input" type="checkbox" name="remember_me" id="remember_me">
        <label class="form-check-label" for="remember_me">
            Remember Me
        </label>
        <br>
        <button type="submit" class="btn btn-primary">Sign In</button>
    </form>
</div>

<?php include 'footer.php'; ?>