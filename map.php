<?php
include 'header.php';
include 'config.php';

require 'vendor\erusev\parsedown\Parsedown.php'; // Include Parsedown for Markdown support
require_once 'vendor/autoload.php'; // Include Composer autoload

// Configure HTMLPurifier
$config = HTMLPurifier_Config::createDefault();
$purifier = new HTMLPurifier($config);

// Fetch all posts
$stmt = $pdo->query("
    SELECT posts.id, posts.title, posts.content, posts.image_path, users.username, posts.created_at, users.profile_photo, location_name, latitude, longitude
    FROM posts 
    JOIN users ON posts.user_id = users.id 
    ORDER BY posts.created_at DESC
");
$posts = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT * FROM flare_posts
");
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

    <!-- Load Map -->
    <script>
        var map = L.map('map').setView([49.214009, -123.070856], 5);
        var posts = <?php echo json_encode($posts); ?>;
        var flare_posts = <?php echo json_encode($flare_posts); ?>;
        var markers = [];
        var flareMarkers = [];

        var greenIcon = new L.Icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        var redIcon = new L.Icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // Add markers for each post
        posts.forEach(post => {
            if (post.latitude && post.longitude && !isNaN(post.latitude) && !isNaN(post.longitude)) {
                var marker = L.marker([parseFloat(post.latitude), parseFloat(post.longitude)], {
                        icon: greenIcon
                    }).addTo(map)
                    .bindPopup(`<div>
                                    <h4>${htmlspecialchars(post.title)}</h4>
                                    <p>By: ${htmlspecialchars(post.username)}</p>
                                    <p>${htmlspecialchars(post.content.slice(0, 100))}...</p>
                                    <a href="post.php?id=${post.id}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded" style="text-decoration: none">View Post</a>
                                    <button onclick="openDirections(${post.latitude}, ${post.longitude})" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded mt-2">Directions</button>
                                </div>`);
                markers.push(marker);
            }
        });

        // Add markers for each flare post
        flare_posts.forEach(flare_post => {
            if (flare_post.latitude && flare_post.longitude && !isNaN(flare_post.latitude) && !isNaN(flare_post.longitude)) {
                var flareMarker = L.marker([parseFloat(flare_post.latitude), parseFloat(flare_post.longitude)], {
                        icon: redIcon
                    }).addTo(map)
                    .bindPopup(`<div>
                                    <h4>${htmlspecialchars(flare_post.title)}</h4>
                                    <p>By: ${htmlspecialchars(flare_post.user)}</p>
                                    <p>${htmlspecialchars(flare_post.description.slice(0, 100))}...</p>
                                    <button onclick="openDirections(${flare_post.latitude}, ${flare_post.longitude})" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded mt-2">Directions</button>
                                </div>`);
                flareMarkers.push(flareMarker);
            }
        });

        // Function to open Google Maps with directions
        function openDirections(lat, lng) {
            var url = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`;
            window.open(url, '_blank');
        }

        // Handle checkbox change
        document.getElementById('toggleMarkers').addEventListener('change', function() {
            if (this.checked) {
                markers.forEach(marker => map.addLayer(marker));
            } else {
                markers.forEach(marker => map.removeLayer(marker));
            }
        });
        document.getElementById('toggleFlareMarkers').addEventListener('change', function() {
            if (this.checked) {
                flareMarkers.forEach(flareMarker => map.addLayer(flareMarker));
            } else {
                flareMarkers.forEach(flareMarker => map.removeLayer(flareMarker));
            }
        });

        // Add locate button
        var userMarker;
        var cachedPosition = null;
        var locateControl = L.control({
            position: 'topright'
        });
        locateControl.onAdd = function(map) {
            var div = L.DomUtil.create('div', 'leaflet-control-locate');
            div.title = 'Locate Me';
            L.DomEvent.on(div, 'click', function(e) {
                L.DomEvent.stopPropagation(e); // Stop the click event from propagating to the map
                var loadingIndicator = document.getElementById('loading-indicator');
                loadingIndicator.style.display = 'block';
                if (cachedPosition) {
                    var lat = cachedPosition.coords.latitude;
                    var lng = cachedPosition.coords.longitude;
                    if (userMarker) {
                        userMarker.setLatLng([lat, lng]);
                    } else {
                        userMarker = L.marker([lat, lng], {
                            icon: redIcon
                        }).addTo(map);
                        userMarker.bindPopup('You are here').openPopup();
                    }
                    map.setView([lat, lng], 13);
                    loadingIndicator.style.display = 'none';
                } else if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        cachedPosition = position;
                        var lat = position.coords.latitude;
                        var lng = position.coords.longitude;
                        if (userMarker) {
                            userMarker.setLatLng([lat, lng]);
                        } else {
                            userMarker = L.marker([lat, lng], {
                                icon: redIcon
                            }).addTo(map);
                            userMarker.bindPopup('You are here').openPopup();
                        }
                        map.setView([lat, lng], 13);
                        loadingIndicator.style.display = 'none';
                    }, function(error) {
                        alert('Error getting location: ' + error.message);
                        loadingIndicator.style.display = 'none';
                    });
                } else {
                    alert('Geolocation is not supported by this browser.');
                    loadingIndicator.style.display = 'none';
                }
            });
            return div;
        };
        locateControl.addTo(map);

        // Function to escape HTML characters
        function htmlspecialchars(str) {
            return str.replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    </script>

</body>

</html>

<?php include 'footer.php'; ?>