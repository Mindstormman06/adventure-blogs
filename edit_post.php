<?php
include 'auth.php';
include 'config.php';
include 'header.php';

if (!isset($_GET['id'])) {
    die("Post ID missing.");
}

$post_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post || ($post['user_id'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin')) {
    die("Access denied.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST["title"]);
    $content = trim($_POST["content"]);
    $location_name = trim($_POST["location_name"]);
    $latitude = !empty($_POST["latitude"]) ? $_POST["latitude"] : null;
    $longitude = !empty($_POST["longitude"]) ? $_POST["longitude"] : null;

    $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, location_name = ?, latitude = ?, longitude = ? WHERE id = ?");
    $stmt->execute([$title, $content, $location_name, $latitude, $longitude, $post_id]);

    header("Location: index.php");
    exit;
}
?>

<div class="container">
    <h2>Edit Post</h2>
    <form method="post">
        <div class="form-group">
            <label>Title:</label>
            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($post['title']); ?>" required>
        </div>

        <div class="form-group">
            <label>Content:</label>
            <textarea name="content" class="form-control" rows="5" required><?php echo htmlspecialchars($post['content']); ?></textarea>
        </div>

        <div class="form-group">
            <label>Location Name:</label>
            <input type="text" name="location_name" id="location_name" class="form-control" value="<?php echo htmlspecialchars($post['location_name']); ?>" placeholder="Enter a location name" value="Tagged Location">
        </div>

        <div class="form-group">
            <label>Select Location on Map:</label>
            <div id="map" style="height: 400px;"></div>
            <input type="hidden" name="latitude" id="latitude" value="<?php echo $post['latitude']; ?>">
            <input type="hidden" name="longitude" id="longitude" value="<?php echo $post['longitude']; ?>">
        </div>

        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="index.php" class="btn btn-secondary">Back to Home</a>
    </form>
</div>

<!-- Leaflet.js for the map -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
    var map = L.map('map').setView([37.7749, -122.4194], 3); // Default zoomed out

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    var latitude = <?php echo !empty($post['latitude']) ? $post['latitude'] : 'null'; ?>;
    var longitude = <?php echo !empty($post['longitude']) ? $post['longitude'] : 'null'; ?>;
    var marker;

    if (latitude !== null && longitude !== null) {
        marker = L.marker([latitude, longitude]).addTo(map);
        map.setView([latitude, longitude], 10);
    }

    function onMapClick(e) {
        if (marker) {
            map.removeLayer(marker);
        }
        marker = L.marker(e.latlng).addTo(map);
        document.getElementById("latitude").value = e.latlng.lat;
        document.getElementById("longitude").value = e.latlng.lng;
    }

    map.on('click', onMapClick);
</script>

<?php include 'footer.php'; ?>
