<?php
include 'auth.php';
include 'config.php';

// Ensure only admins can access
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user['role'] !== 'admin') {
    die("Access denied. Admins only.");
}

// Handle role updates
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_POST['user_id'];
    $newRole = $_POST['role'];

    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$newRole, $userId]);

    echo "<p>User role updated successfully!</p>";
}

// Fetch all users
$users = $pdo->query("SELECT id, username, role FROM users")->fetchAll();
?>

<h2>Admin Panel - Manage Users</h2>
<table border="1">
    <tr>
        <th>Username</th>
        <th>Role</th>
        <th>Action</th>
    </tr>
    <?php foreach ($users as $u): ?>
    <tr>
        <td><?php echo htmlspecialchars($u['username']); ?></td>
        <td><?php echo htmlspecialchars($u['role']); ?></td>
        <td>
            <form method="post">
                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                <select name="role">
                    <option value="user" <?php if ($u['role'] == 'user') echo "selected"; ?>>User</option>
                    <option value="admin" <?php if ($u['role'] == 'admin') echo "selected"; ?>>Admin</option>
                </select>
                <button type="submit">Update</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<a href="index.php">Back to Home</a>
