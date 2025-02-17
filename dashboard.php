<?php 
include 'auth.php'; 
include 'header.php'; 
include 'config.php';

// Check if user is an admin or user
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user['role'] !== 'user' && $user['role'] !== 'admin') {
    die("Access denied. Only registered users can create posts.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST["title"]);
    $content = trim($_POST["content"]);
    $user_id = $_SESSION["user_id"];
    $imagePath = null;
    $location_name = trim($_POST["location_name"]);
    $latitude = !empty($_POST["latitude"]) ? $_POST["latitude"] : null;
    $longitude = !empty($_POST["longitude"]) ? $_POST["longitude"] : null;

    // Validate title and content
    if (empty($title) || empty($content)) {
        echo "<p>Error: Both title and content must be filled in.</p>";
    } elseif (strlen($content) > 1000) {
        echo "<p>Error: Content exceeds the 1000-character limit.</p>";
    } else {
        // Handle Image Upload
        if (!empty($_FILES["image"]["name"])) {
            $maxFileSize = 32 * 1024 * 1024; // 32MB max
            if ($_FILES["image"]["size"] > $maxFileSize) {
                echo "<p>Error: File is too large.</p>";
                exit;
            }
            $targetDir = "uploads/";
            $imagePath = $targetDir . basename($_FILES["image"]["name"]);
            move_uploaded_file($_FILES["image"]["tmp_name"], $imagePath);
        }

        // Insert post into database
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content, image_path, location_name, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $content, $imagePath, $location_name, $latitude, $longitude]);

        echo "<p>Post uploaded successfully! <a href='index.php'>View posts</a></p>";
        header("Location: index.php");
        exit;
    }
}
?>

<div class="container">
    <h2>Create a New Post</h2>
    <form method="post" enctype="multipart/form-data" onsubmit="return validatePost()">
        <label>Title:</label>
        <input type="text" name="title" id="title" required>

        <label>Content:</label>
        <textarea name="content" id="content" required maxlength="1000"></textarea>
        <p id="content-char-count">0/1000 characters used</p>

        <p><i>Markdown is supported! Click <a href="https://github.com/adam-p/markdown-here/wiki/markdown-cheatsheet">here</a> for a guide on using Markdown</i></p>

        <label>Upload Media:</label>
        <input type="file" name="image" required>

        <label>Tags (comma-separated):</label>
        <input type="text" name="tags">

        <!-- Location Selection -->
        <label>Location Name:</label>
        <input type="text" name="location_name" id="location_name" placeholder="Enter a location name" value="Tagged Location">

        <label>Select Location on Map:</label>
        <div id="map" style="height: 400px;"></div>
        <input type="hidden" name="latitude" id="latitude">
        <input type="hidden" name="longitude" id="longitude">

        <button type="submit">Post</button>
    </form>
</div>

<!-- Leaflet.js for the map -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
    // Real-time content length display
    document.getElementById("content").addEventListener("input", function() {
        var charCount = this.value.length;
        document.getElementById("content-char-count").textContent = charCount + "/1000 characters used";
    });

    function validatePost() {
        var title = document.getElementById("title").value;
        var content = document.getElementById("content").value;
        
        if (title.trim() === "" || content.trim() === "") {
            alert("Both the title and content must be filled in.");
            return false;
        }

        if (content.length > 1000) {
            alert("Content exceeds the 1000-character limit.");
            return false;
        }

        return true;
    }

    // Initialize map
    var map = L.map('map').setView([37.7749, -122.4194], 3); // Default to a wide zoom level

    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    var marker;

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
