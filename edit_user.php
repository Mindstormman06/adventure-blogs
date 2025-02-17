<?php
include 'header.php';
include 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");  // Redirect if not logged in
    exit();
}

// Get user data
$stmt = $pdo->prepare("SELECT id, username, email, password_hash FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user posts
$stmt = $pdo->prepare("SELECT id, title, created_at FROM posts WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$posts = $stmt->fetchAll();
?>

<div class="container">
    <h1>Update Profile</h1>

    <form action="update_profile.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
        </div>
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>
        <div class="form-group">
            <label for="current_password">Current Password (Required):</label>
            <input type="password" name="current_password" id="current_password" required>
        </div>
        <div class="form-group">
            <label for="password">New Password (leave blank to keep current):</label>
            <input type="password" name="password" id="password">
        </div>

        <div class="form-group">
            <label for="profile_photo">Profile Photo:</label>
            <input type="file" name="profile_photo" id="profile_photo" accept="image/*">
        </div>

        <div class="form-group">
            <label for="instagram_link">Instagram Link:</label>
            <input type="url" name="instagram_link" id="instagram_link" value="<?php echo htmlspecialchars($user['instagram_link'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="website_link">Website Link:</label>
            <input type="url" name="website_link" id="website_link" value="<?php echo htmlspecialchars($user['website_link'] ?? ''); ?>">
        </div>

        <button type="submit">Update Profile</button>
    </form>
</div>


<?php include 'footer.php'; ?>
