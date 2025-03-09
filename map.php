<?php
include 'header.php';
include 'config.php';

require 'vendor\erusev\parsedown\Parsedown.php'; // Include Parsedown for Markdown support
require_once 'vendor/autoload.php'; // Include Composer autoload
require 'models/Post.php'; // Include the Post class

// Configure HTMLPurifier
$config = HTMLPurifier_Config::createDefault();
$purifier = new HTMLPurifier($config);

// Initialize Parsedown
$Parsedown = new Parsedown(); // Initialize Parsedown

// Create Post object
$postObj = new Post($pdo, $Parsedown, $purifier);

// Fetch all posts
$posts = $postObj->getAllPosts();

// Fetch all flare posts
$stmt = $pdo->query("SELECT * FROM flare_posts");
$flare_posts = $stmt->fetchAll();

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

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            max-width: 80vw;
            margin: 0 auto;
            flex-direction: column;
            margin-top: 25px;
            margin-bottom: 25px;
        }

        #map {
            height: 75vh;
            width: 100%;
            max-width: 100%;
            border-radius: 10px;
            margin-top: 15px;
            margin-bottom: 15px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }

        .leaflet-control-locate {
            background-color: white;
            background-image: url('https://cdn-icons-png.flaticon.com/512/684/684908.png');
            background-size: 20px 20px;
            background-repeat: no-repeat;
            background-position: center;
            width: 30px;
            height: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
            cursor: pointer;
        }

        .loading-indicator {
            display: none;
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(255, 255, 255, 0.8);
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>

<body>

    <!-- Display the map -->
    <div class="container map-container">
        <h1>Post Map</h1>
        <p>Explore the locations of our posts and flares.</p>
        <div style="flex-direction: row;">
            <label>
                <input type="checkbox" id="toggleMarkers" checked>
                Show AB Markers
            </label>
            <label>
                <input type="checkbox" id="toggleFlareMarkers" checked>
                Show Flare Markers
            </label>
        </div>
        <div id="map"></div>
        <div id="loading-indicator" class="loading-indicator">Fetching location...</div>
    </div>

    <!-- Load Leaflet from CDN -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <!-- Include the new JavaScript file -->
    <script>
        let posts = <?php echo json_encode($posts); ?>;
        let flare_posts = <?php echo json_encode($flare_posts); ?>;
        let latitude = <?php echo $latitude; ?>;
        let longitude = <?php echo $longitude; ?>;
    </script>
    <script src="js/MapHandler.js"></script>

</body>

</html>

<?php include 'footer.php'; ?>