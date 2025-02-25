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

// Handle Post Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST["title"]);
    $content = trim($_POST["content"]);
    $user_id = $_SESSION["user_id"];
    $latitude = !empty($_POST["latitude"]) ? $_POST["latitude"] : null;
    $longitude = !empty($_POST["longitude"]) ? $_POST["longitude"] : null;
    $tagsInput = trim($_POST["tags"]);
    if (isset($latitude) && isset($longitude)) {
        $location_name = !empty($_POST['location_name']) ? $_POST['location_name'] : "Tagged Location";
    } else {
        $location_name = null;
    }

    // Validate title and content
    if (empty($title)) {
        echo "<p>Error: Title must be filled in.</p>";
    } elseif (strlen($content) > 1000) {
        echo "<p>Error: Content exceeds the 1000-character limit.</p>";
    } else {
        // Handle Image Upload
        if (!empty($_FILES["image"]["name"])) {
            $maxFileSize = 100 * 1024 * 1024; // 100MB max
            if ($_FILES["image"]["size"] > $maxFileSize) {
                echo "<p>Error: File is too large.</p>";
                exit;
            }
            $targetDir = "uploads/";
            $imagePath = $targetDir . basename($_FILES["image"]["name"]);
            move_uploaded_file($_FILES["image"]["tmp_name"], $imagePath);
        }

        // Insert post into database
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content, location_name, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $content, $location_name, $latitude, $longitude]);
        $post_id = $pdo->lastInsertId();

        // Allowed File Data
        $allowedTypes = [
            "image/jpeg", "image/png", "image/gif",
            "video/mp4", "video/webm", "video/quicktime",
            "audio/mpeg", "audio/wav", "audio/ogg", "audio/mp4", "audio/x-m4a", "audio/flac"
        ];
        $maxFileSize = 100 * 1024 * 1024; // 32MB max per file
        $targetDir = "uploads/";

        // Handle file validation
        if (!empty($_FILES["images"]["name"][0])) {
            $totalFiles = count($_FILES["images"]["name"]);
            if ($totalFiles > 10) {
                echo "<p>Error: You can upload a maximum of 10 files.</p>";
                exit;
            }

            for ($i = 0; $i < $totalFiles; $i++) {

                // Check file size
                if ($_FILES["images"]["size"][$i] > $maxFileSize) {
                    echo "<p>Error: File " . $_FILES["images"]["name"][$i] . " is too large.</p>";
                    exit;
                }

                // Check file type
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $fileType = finfo_file($finfo, $_FILES["images"]["tmp_name"][$i]);
                finfo_close($finfo);
                if (!in_array($fileType, $allowedTypes)) {
                    echo "<p>Error: Invalid file type for " . $_FILES["images"]["name"][$i] . ".</p>";
                    exit;
                }

                // Get file extension
                $fileExt = pathinfo($_FILES["images"]["name"][$i], PATHINFO_EXTENSION);

                // Store Original File Name
                $originalFileName = pathinfo($_FILES["images"]["name"][$i], PATHINFO_FILENAME);

                // Rename file to include post ID and timestamp
                $customFileName = $post_id . "-" . time() . "-" . $i . "." . $fileExt;

                // Move file to uploads directory
                $filePath = $targetDir . $customFileName;
                move_uploaded_file($_FILES["images"]["tmp_name"][$i], $filePath);

                // Convert FLAC to MP3 (UNCOMMENT IF NEEDED)

                // if ($fileExt === "flac") {
                //     $mp3FilePath = str_replace(".flac", ".mp3", $filePath);
                //     $ffmegPath = "\"C:\\ffmpeg-master-latest-win64-gpl\\bin\\ffmpeg.exe\"";
                //     $ffmpegCmd = $ffmegPath . " -i " . escapeshellarg($filePath) . " -ab 192k -y " . escapeshellarg($mp3FilePath) . " 2>&1";
                //     shell_exec($ffmpegCmd);
        
                //     echo "<pre>";
                //     echo "Command: " . $ffmpegCmd . "\n";

                //     unlink($filePath);

                //     $filePath = $mp3FilePath;
                   
                // }

                // Insert file path into post_files table
                $stmt = $pdo->prepare("INSERT INTO post_files (post_id, file_path, original_filename) VALUES (?, ?, ?)");
                $stmt->execute([$post_id, $filePath, $originalFileName]);
                $fileId = $pdo->lastInsertId();
            }
        }

        // Insert tags into database
        if (!empty($tagsInput)) {
            $tagsArray = array_map('trim', explode(',', $tagsInput));
            foreach ($tagsArray as $tag) {
                $stmt = $pdo->prepare("SELECT id FROM tags WHERE name = ?");
                $stmt->execute([$tag]);
                $tag_id = $stmt->fetchColumn();
            
                if (!$tag_id) {
                    $stmt = $pdo->prepare("INSERT INTO tags (name) VALUES (?)");
                    $stmt->execute([$tag]);
                    $tag_id = $pdo->lastInsertId();
                }
            
                $pdo->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)")->execute([$post_id, $tag_id]);
            }
        }

        echo "<p>Post uploaded successfully! <a href='index.php'>View posts</a></p>";
        header("Location: index.php");
        exit;
    }
}
?>
<head>
    <style>
        /* Tagify input field styling */
        .tagify__input {
            min-width: 150px; /* Adjust width as needed */
            padding-left: 10px;
            padding-right: 10px;
            display: inline-block; /* Ensure it's displayed inline-block */
            vertical-align: middle; /* Align it in the middle */
        }

        .tagify {
            display: flex;
            align-items: center;
            min-height: 38px; /* Ensuring the container has a consistent height */
            border: 1px solid #ccc; /* Adding border for a complete input field appearance */
            padding: 5px; /* Adding padding for a consistent look */
        }

        /* Placeholder text styling */
        .tagify__input::placeholder {
            opacity: 0.5; /* Adjust opacity for better visibility */
            padding-left: 10px; /* Ensure placeholder is well-aligned */
        }

        /* Add this inside the <style> tag */
        .leaflet-control-locate {
            background-color: white;
            background-image: url('https://cdn-icons-png.flaticon.com/512/684/684908.png');
            background-size: 20px 20px;
            background-repeat: no-repeat;
            background-position: center;
            width: 30px;
            height: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
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
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }
    </style>
</head>
<div class="container">
    <h2>Create a New Post</h2>
    <form method="post" enctype="multipart/form-data" onsubmit="return validatePost()">
       
        <!-- Title -->
        <div class="form-group">
            <label>Title:</label>
            <input type="text" name="title" id="title" required>
        </div>

        <!-- Content -->
        <div class="form-group">
            <label>Content:</label>
            <textarea name="content" id="content" maxlength="1000"></textarea>
            <small id="content-char-count">0/1000 characters used</small>
            <br>
            <small><i>Markdown is supported! Click <a href="https://github.com/adam-p/markdown-here/wiki/markdown-cheatsheet">here</a> for a guide on using Markdown</i></small>
        </div>

        <!-- File Upload -->
        <div class="form-group">
            <label>Upload Media (max 10 files):</label>
            <input type="file" name="images[]" id="file_input" multiple accept="image/*,video/*, audio/*" required>
            <div id="fileErrors" style="color: red; margin-top: 10px;"></div>
            <div id="filePreview" style="margin-top: 10px;"></div>
        </div>

        <!-- Tags -->
        <div class="form-group">
            <label>Tags (Hit Enter after each tag):</label>
            <input type="text" name="tags" placeholder="e.g. travel, adventure, hiking">
        </div>    

        <!-- Location Name -->
        <div class="form-group">
            <label>Location Name:</label>
            <input type="text" name="location_name" id="location_name" placeholder="Enter a location name" value="Tagged Location">
        </div>

        <!-- Location Selection -->
        <div class="form-group">
            <label>Select Location on Map:</label>
            <div id="map" style="height: 400px;"></div>
            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">
        </div>

        <div id="loading-indicator" class="loading-indicator">Fetching location...</div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-success" style="margin-top: 20px">Post</button>

    </form>
</div>

<!-- Leaflet.js for the map -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<!-- File Validation Script -->
<script>
    document.getElementById("file_input").addEventListener("change", function (event) {
        var fileErrorsDiv = document.getElementById("fileErrors");
        var filePreviewDiv = document.getElementById("filePreview");
        fileErrorsDiv.innerHTML = ""; // Clear previous errors
        filePreviewDiv.innerHTML = ""; // Clear previous previews

        var files = event.target.files;
        var allowedTypes = [
            "image/jpeg", "image/png", "image/gif",
            "video/mp4", "video/webm", "video/quicktime",
            "audio/mpeg", "audio/wav", "audio/ogg", "audio/mp4", "audio/x-m4a", "audio/x-flac"
        ];
        var maxFileSize = 100 * 1024 * 1024; // 32MB per file
        var maxFiles = 10;

        if (files.length > maxFiles) {
            fileErrorsDiv.innerHTML = `<p>Error: You can upload a maximum of ${maxFiles} files.</p>`;
            event.target.value = ""; // Reset file input
            return;
        }

        for (var i = 0; i < files.length; i++) {
            var file = files[i];

            // Check file type
            if (!allowedTypes.includes(file.type)) {
                fileErrorsDiv.innerHTML += `<p>Error: ${file.name} is not an allowed file type.</p>`;
                event.target.value = ""; // Reset file input
                return;
            }

            // Check file size
            if (file.size > maxFileSize) {
                fileErrorsDiv.innerHTML += `<p>Error: ${file.name} exceeds the 100MB limit.</p>`;
                event.target.value = ""; // Reset file input
                return;
            }

            // OPTIONAL: Show image/video previews
            if (file.type.startsWith("image/")) {
                var img = document.createElement("img");
                img.src = URL.createObjectURL(file);
                img.style.maxWidth = "100px";
                img.style.margin = "5px";
                filePreviewDiv.appendChild(img);
            } else if (file.type.startsWith("video/")) {
                var vid = document.createElement("video");
                vid.src = URL.createObjectURL(file);
                vid.controls = true;
                vid.style.maxWidth = "150px";
                vid.style.margin = "5px";
                filePreviewDiv.appendChild(vid);
            }
        }
    });

</script>

<!-- Post Validation Script + Map Script -->
<script>
    // Real-time content length display
    document.getElementById("content").addEventListener("input", function() {
        var charCount = this.value.length;
        document.getElementById("content-char-count").textContent = charCount + "/1000 characters used";
    });

    function validatePost() {
        var title = document.getElementById("title").value;
        var content = document.getElementById("content").value;
        var fileErrorsDiv = document.getElementById("fileErrors");

        if (title.trim() === "") {
            alert("Title must be filled in.");
            return false;
        }

        if (content.length > 1000) {
            alert("Content exceeds the 1000-character limit.");
            return false;
        }

        if (fileErrorsDiv.innerHTML !== "") {
            alert("Please fix file upload errors before submitting.");
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

    var greenIcon = new L.Icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

    function onMapClick(e) {
        if (marker) {
            map.removeLayer(marker);
        }
        marker = L.marker(e.latlng, { icon: greenIcon }).addTo(map);
        document.getElementById("latitude").value = e.latlng.lat;
        document.getElementById("longitude").value = e.latlng.lng;
    }

    map.on('click', onMapClick);

    // Add this inside the <script> tag that initializes the map
    var userMarker;
    var cachedPosition = null;
    var locateControl = L.control({position: 'topright'});
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
                    userMarker = L.marker([lat, lng], { icon: greenIcon }).addTo(map);
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
                        userMarker = L.marker([lat, lng], { icon: greenIcon }).addTo(map);
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
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var input = document.querySelector('input[name=tags]');
        var removedTagsInput = document.createElement('input');

        var tagify = new Tagify(input);

        // Convert Tagify output to a simple comma-separated string before submitting the form
        document.querySelector('form').addEventListener('submit', function() {
            var tagsArray = tagify.value.map(tag => tag.value);
            input.value = tagsArray.join(',');
        });
    });
</script>

<?php include 'footer.php'; ?>
