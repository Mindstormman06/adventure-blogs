<?php
session_start(); // Start the session

// Include database connection
include 'config.php';
include 'header.php';

$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $photo = $_FILES['photo']['name'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $user = $_SESSION['username']; // Get the current user's name from the session

    // Check if either description or photo is provided
    if (!empty($description) || !empty($photo)) {
        // Upload photo if provided
        if (!empty($photo)) {
            $target_dir = "uploads/";
            $target_file = $target_dir . basename($_FILES["photo"]["name"]);
            move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file);
        }

        // Insert post into database
        $sql = "INSERT INTO flare_posts (title, description, photo_path, latitude, longitude, user) VALUES (:title, :description, :photo_path, :latitude, :longitude, :user)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':photo_path', $photo);
        $stmt->bindParam(':latitude', $latitude);
        $stmt->bindParam(':longitude', $longitude);
        $stmt->bindParam(':user', $user);

        if ($stmt->execute()) {
            // Redirect to prevent form resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            echo "Error: " . $stmt->errorInfo()[2];
        }
    } else {
        echo "Please provide either a description or a photo.";
    }
}

// Fetch all posts from the database
$sql = "SELECT * FROM flare_posts";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Create Flare Post</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <style>
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
            background-color: #ffac38;
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

        header {
            background-color: #e75447;
        }

        nav {
            background-color: #e75447;
        }

        footer {
            background-color: #e75447;
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
    <script>
        function initMap() {
            var map = L.map('map').setView([49.214009, -123.070856], 5);
            var redIcon = new L.Icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            map.on('click', function(e) {
                var popupContent = `
                    <form method="post" enctype="multipart/form-data" class="container" style="width: 15vw;">
                        <h2>Create Flare</h2>
                        <label for="title">Title:</label>
                        <input type="text" id="title" name="title" required><br><br>
                        <label for="description">Description:</label>
                        <textarea id="description" name="description"></textarea><br><br>
                        <label for="photo">Photo:</label>
                        <input type="file" id="photo" name="photo" accept="image/*"><br><br>
                        <input type="hidden" id="latitude" name="latitude" value="${e.latlng.lat}">
                        <input type="hidden" id="longitude" name="longitude" value="${e.latlng.lng}">
                        <input type="submit" value="Create Flare">
                    </form>
                `;
                L.popup()
                    .setLatLng(e.latlng)
                    .setContent(popupContent)
                    .openOn(map);
            });

            // Add markers for each post
            var posts = <?php echo json_encode($posts); ?>;
            var currentUser = '<?php echo $_SESSION['username']; ?>';
            var isAdmin = <?php echo json_encode($isAdmin); ?>;
            posts.forEach(function(post) {
                var marker = L.marker([post.latitude, post.longitude], {
                    icon: redIcon
                }).addTo(map);
                var popupContent = `
                    <h3>${post.title}</h3>
                    <p>by ${post.user}</p>
                    <p>${post.description}</p>
                    ${post.photo_path ? `<img src="uploads/${post.photo_path}" alt="${post.title}" style="width:100px;height:auto;">` : ''}
                    <button onclick="openDirections(${post.latitude}, ${post.longitude})">Directions</button>
                    ${(post.user === currentUser || isAdmin) ? `<button onclick="deleteFlare(${post.id})">Delete</button>` : ''}
                `;
                marker.bindPopup(popupContent);
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
        }

        // Function to open Google Maps with directions
        function openDirections(lat, lng) {
            var url = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`;
            window.open(url, '_blank');
        }

        function deleteFlare(flarId) {
            if (confirm('Are you sure you want to delete this flare?')) {
                fetch('delete_flare.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id: flarId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error deleting flare: ' + data.message);
                        }
                    });
            }
        }
    </script>
</head>

<body onload="initMap()">
    <div class="container">
        <h1>🔥 Flare 🔥</h1>
        <small>Click to create a flare!</small>

        <div id="map"></div>
        <div id="loading-indicator" class="loading-indicator">Fetching location...</div>
    </div>
    <?php include 'footer.php'; ?>
</body>

</html>