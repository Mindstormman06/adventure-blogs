<?php
include 'header.php';
include 'config.php';

// Fetch all posts
$stmt = $pdo->query("
    SELECT posts.id, posts.title, posts.content, posts.image_path, users.username, posts.created_at, users.profile_photo, location_name, latitude, longitude
    FROM posts 
    JOIN users ON posts.user_id = users.id 
    ORDER BY posts.created_at DESC
");
$posts = $stmt->fetchAll();

// Ensure map has a valid starting point
if (!empty($posts) && isset($posts[0]['latitude'], $posts[0]['longitude'])) {
    $latitude = floatval($posts[0]['latitude']);
    $longitude = floatval($posts[0]['longitude']);
} else {
    $latitude = 0;  // Default center (change if needed)
    $longitude = 0;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Locations</title>

    <!-- Load Leaflet from CDN -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        #map { 
            height: 60vh;
            width: 100%; 
        }
    </style>
</head>
<body>

    <!-- Display the map -->
    <div class="container map-container">
        <label>
            <input type="checkbox" id="toggleMarkers" checked>
            Show AB Markers
        </label>
        <label>
            <input type="checkbox" id="toggleFlareMarkers">
            Show Flare Markers
        </label>
        <div id="map"></div>
        <a href="index.php" class="btn btn-primary">⬅ Back to Posts</a>
    </div>

    <script>
        document.getElementById("toggleFlareMarkers").disabled = true;
    </script>

    <!-- Load Leaflet from CDN -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <!-- Load Map -->
    <script>
        var map = L.map('map').setView([49.214009, -123.070856], 5);
        var posts = <?php echo json_encode($posts); ?>;
        var markers = [];

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        posts.forEach(post => {
            if (post.latitude && post.longitude && !isNaN(post.latitude) && !isNaN(post.longitude)) {
                var marker = L.marker([parseFloat(post.latitude), parseFloat(post.longitude)]).addTo(map)
                    .bindPopup(`<div>
                                    <h4>${post.title}</h4>
                                    <p>By: ${post.username}</p>
                                    <p>${post.content.slice(0, 100)}...</p>
                                    <a href="post.php?id=${post.id}">View Post</a>
                                    <small>Origin: Adventure Blogs</small>
                                </div>`);
                markers.push(marker);
            }
        });

        // Handle checkbox change
        document.getElementById('toggleMarkers').addEventListener('change', function() {
            if (this.checked) {
                markers.forEach(marker => map.addLayer(marker));
            } else {
                markers.forEach(marker => map.removeLayer(marker));
            }
        });
    </script>

</body>
</html>

<?php include 'footer.php'; ?>