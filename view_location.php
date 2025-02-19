<?php
include 'header.php';

if (!isset($_GET['lat']) || !isset($_GET['lng'])) {
    die("Invalid location.");
}

// Get the location details from the URL
$latitude = htmlspecialchars($_GET['lat']);
$longitude = htmlspecialchars($_GET['lng']);
$location_name = htmlspecialchars($_GET['name']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Post Location</title>

    <!-- Load Leaflet from CDN -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
</head>
<body>

    <!-- Display the map -->
    <div class="container map-container">
        <h2><?php echo $location_name ?></h2>
        <div id="map"></div>
        <a href="index.php" class="btn btn-primary">⬅ Back to Posts</a>
    </div>

    <!-- Load Leaflet from CDN -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <!-- Load Map -->
    <script>
        var map = L.map('map').setView([<?php echo $latitude; ?>, <?php echo $longitude; ?>], 12);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        L.marker([<?php echo $latitude; ?>, <?php echo $longitude; ?>]).addTo(map)
            .bindPopup("<?php echo $location_name ?>").openPopup();
    </script>

</body>
</html>

<?php include 'footer.php'; ?>
