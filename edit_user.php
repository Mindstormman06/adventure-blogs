<?php
include 'header.php';
include 'config.php';
require 'models/User.php'; // Include the User class

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userObj = new User($pdo);

// Get user data
$user = $userObj->getUserById($_SESSION['user_id']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $currentPassword = $_POST["current_password"];
    $newPassword = $_POST["password"];
    $instagramLink = trim($_POST["instagram_link"]);
    $websiteLink = trim($_POST["website_link"]);
    $profilePhotoPath = null;

    // Verify current password
    if (!password_verify($currentPassword, $user['password_hash'])) {
        die("Current password is incorrect.");
    }

    // Hash new password if provided
    $passwordHash = $user['password_hash'];
    if (!empty($newPassword)) {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    }

    // Handle profile photo upload
    if (!empty($_FILES["profile_photo"]["name"])) {
        $files = $_FILES["profile_photo"];
        $allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/gif",
            "video/mp4",
            "video/webm",
            "video/quicktime",
            "audio/mpeg",
            "audio/wav",
            "audio/ogg",
            "audio/mp4",
            "audio/x-m4a",
            "audio/flac"
        ];
        $uploadDir = "profile_photos/";

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $fileType = finfo_file($finfo, $files["tmp_name"]);
        finfo_close($finfo);
        $profilePhotoPath = $uploadDir . basename($_FILES["profile_photo"]["name"]);
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception("Invalid file type for " . $files["name"][$i] . ".");
        }
        move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $profilePhotoPath);
    }

    // Update user profile
    $userObj->updateUserProfile($_SESSION['user_id'], $username, $email, $passwordHash, $instagramLink, $websiteLink, $profilePhotoPath);

    // Refresh user data
    $user = $userObj->getUserById($_SESSION['user_id']);
}
?>

<div class="container">
    <h1>Update Profile</h1>

    <form action="edit_user.php" method="POST" enctype="multipart/form-data">

        <!-- Username -->
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
        </div>

        <!-- Email -->
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>

        <!-- Current password -->
        <div class="form-group">
            <label for="current_password">Current Password (Required):</label>
            <input type="password" name="current_password" id="current_password" required>
        </div>

        <!-- Update password -->
        <div class="form-group">
            <label for="password">New Password (leave blank to keep current):</label>
            <input type="password" name="password" id="password">
        </div>

        <!-- Update profile photo -->
        <div class="form-group">
            <label for="profile_photo">Profile Photo:</label>
            <input type="file" name="profile_photo" id="profile_photo" accept="image/*">
        </div>

        <!-- Update instagram link -->
        <div class="form-group">
            <label for="instagram_link">Instagram Link:</label>
            <input type="url" name="instagram_link" id="instagram_link" value="<?php echo htmlspecialchars($user['instagram_link'] ?? ''); ?>">
        </div>

        <!-- Update website link -->
        <div class="form-group">
            <label for="website_link">Website Link:</label>
            <input type="url" name="website_link" id="website_link" value="<?php echo htmlspecialchars($user['website_link'] ?? ''); ?>">
        </div>

        <button type="submit" class="btn btn-success" style="margin-top: 10px">Update Profile</button>
    </form>
</div>

<?php include 'footer.php'; ?>