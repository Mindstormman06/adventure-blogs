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
        <div id="map"></div>
        <a href="index.php" class="btn btn-primary">⬅ Back to Posts</a>
    </div>

    <!-- Load Leaflet from CDN -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <!-- Load Map -->
    <script>
        var map = L.map('map').setView([49.214009, -123.070856], 5);
        var posts = <?php echo json_encode($posts); ?>;

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        posts.forEach(post => {
            if (post.latitude && post.longitude && !isNaN(post.latitude) && !isNaN(post.longitude)) {
                L.marker([parseFloat(post.latitude), parseFloat(post.longitude)]).addTo(map)
                    .bindPopup(post.location_name);
            }
        });

    </script>

</body>
</html>
