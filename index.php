<?php include 'header.php'; ?>
<?php include 'config.php'; ?>

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'config.php';

$user = null;

$userRole = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $userRole = $user ? $user['role'] : null;

}
$videoFileTypes = ['mp4', 'ogg', 'webm', 'mov'];

$stmt = $pdo->query("
    SELECT posts.id, posts.title, posts.content, posts.image_path, users.username 
    FROM posts 
    JOIN users ON posts.user_id = users.id 
    ORDER BY posts.created_at DESC
");
$posts = $stmt->fetchAll();
// echo '<pre>'; print_r($user); echo '</pre>';

?>

<div class="container">
    <h1>Recent Posts</h1>

    <?php foreach ($posts as $post): 
        // Get the file extension
        $fileExtension = pathinfo($post['image_path']);
        $isVideo = in_array(strtolower($fileExtension['extension']), $videoFileTypes);
        $isImage = !$isVideo && !empty($post['image_path']); // Ensure it's not empty and not a video
        $postUserID = $post['username'];
    ?>
        <div class="post">
            <h2><?php echo htmlspecialchars($post['title']); ?></h2>
            <p><i>By <?php echo htmlspecialchars($post['username']); ?></i></p>
            <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>

            <?php if ($isVideo): ?>
                <video style="max-width: 500px; max-height: 500px; min-width: 100px; min-height: 100px;" controls src="<?php echo htmlspecialchars($post['image_path']); ?>" autoplay muted loop>
                    Your browser does not support the video tag.
                </video>
            <?php endif; ?>

            <?php if ($isImage): ?>
                <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Failed to load image" style="max-width: 500px; max-height: 500px;">
            <?php endif; ?>

            <p><a class="btn" href="post.php?id=<?php echo $post['id']; ?>">View Comments</a></p>

            <?php if (isset($_SESSION['user_id']) && $user && ($_SESSION['username'] == $post['username'] || $user['role'] == 'admin')): ?>
                <p class="post_controls">
                    <a class="btn btn-warning" href="edit_post.php?id=<?php echo $post['id']; ?>">Edit</a>
                    <a class="btn btn-danger" href="delete_post.php?id=<?php echo $post['id']; ?>" 
                    onclick="return confirm('Are you sure you want to delete this post?');">Delete</a>
                </p>
                
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php include 'footer.php'; ?>
