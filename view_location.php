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
        var greenIcon = new L.Icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        L.marker([<?php echo $latitude; ?>, <?php echo $longitude; ?>], { icon: greenIcon }).addTo(map)
            .bindPopup("<?php echo $location_name ?>").openPopup();
    </script>

</body>
</html>

<?php include 'footer.php'; ?>
