<?php include 'header.php'; ?>
<?php include 'config.php'; ?>
<?php require 'models/User.php'; // Include the User class ?>

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $remember = isset($_POST["remember_me"]);

    $userObj = new User($pdo);

    try {
        $user = $userObj->login($username, $password);

        // Set the session variables
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $username;
        $_SESSION["role"] = $user["role"];

        if ($remember) {
            $userObj->rememberUser($user["id"]);
        }

        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        echo "<p>" . $e->getMessage() . "</p>";
    }
}
?>

<div class="container">
    <h2>Adventure Blogs</h2>
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