<?php
include 'header.php';

if (!isset($_GET['lat']) || !isset($_GET['lng'])) {
    die("Invalid location.");
}

$latitude = htmlspecialchars($_GET['lat']);
$longitude = htmlspecialchars($_GET['lng']);
$location_name = htmlspecialchars($_GET['name']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Post Location</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        #map {
            height: 500px;
            width: 100%;
            border-radius: 10px;
            margin-top: 15px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }
        .container {
            max-width: 800px;
            margin: auto;
            text-align: center;
            padding: 20px;
        }
        .btn-back {
            margin-top: 15px;
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: 0.3s;
        }
        .btn-back:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><?php echo $location_name ?></h2>
        <div id="map"></div>
        <a href="index.php" class="btn-back">⬅ Back to Posts</a>
    </div>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
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
