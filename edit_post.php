<?php
include 'auth.php';
include 'config.php';
include 'header.php';

// Check if post exists
if (!isset($_GET['id'])) {
    die("Post ID missing.");
}

// Fetch post data
$post_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if post belongs to the current user or if the user is an admin
if (!$post || ($post['user_id'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin')) {
    die("Access denied.");
}

// Handle image removals
if (!empty($_POST['remove_images'])) {
    foreach ($_POST['remove_images'] as $imagePath) {
        // Delete file from the server
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }

        // Remove entry from the database
        $stmt = $pdo->prepare("DELETE FROM post_files WHERE post_id = ? AND file_path = ?");
        $stmt->execute([$post_id, $imagePath]);
    }
}


// Fetch existing images
$imageStmt = $pdo->prepare("SELECT file_path FROM post_files WHERE post_id = ?");
$imageStmt->execute([$post_id]);
$existingImages = $imageStmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch current tags
$tagStmt = $pdo->query("
    SELECT tags.id, tags.name, post_tags.post_id
    FROM tags
    INNER JOIN post_tags ON tags.id = post_tags.tag_id");
$tags = $tagStmt->fetchAll();

function getTags($tags1, $post1) {
    $postTagsArray = [];

    foreach ($tags1 as $tag) {
        if ($tag['post_id'] == $post1['id']) {   
            $postTagsArray[] = htmlspecialchars($tag['name']);       
        }
    }

    return implode(", ", $postTagsArray);
}
// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST["title"]);
    $content = trim($_POST["content"]);
    $latitude = !empty($_POST["latitude"]) ? $_POST["latitude"] : null;
    $longitude = !empty($_POST["longitude"]) ? $_POST["longitude"] : null;
    $tagsInput = trim($_POST["tags"]);
    if (isset($latitude) && isset($longitude)) {
        $location_name = !empty($_POST['location_name']) ? $_POST['location_name'] : "Tagged Location";
    } else {
        $location_name = null;
    }


    // Allowed File Data
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/webm', 'video/quicktime', 'audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp4', 'audio/x-m4a', 'audio/x-flac', 'audio/flac'];
    $maxFileSize = 100 * 1024 * 1024; // MB max per file
    $uploadDir = "uploads/";

    // Handle file validation
    if (!empty($_FILES["images"]["name"][0])) {
        foreach ($_FILES["images"]["tmp_name"] as $key => $tmp_name) {
            $fileType = mime_content_type($tmp_name);
            $fileSize = $_FILES["images"]["size"][$key];
            if (!in_array($fileType, $allowedTypes) || $fileSize > $maxFileSize) {
                echo "<p>Error: Invalid file type or size too large.</p>";
            }

            // Get file extension
            $fileExt = pathinfo($_FILES["images"]["name"][$key], PATHINFO_EXTENSION);

            // Rename file to include post ID and timestamp
            $customFileName = $post_id . "-" . time() . "-" . $key . "." . $fileExt;
            
            // Move file to uploads directory
            $filePath = $uploadDir . $customFileName;
            move_uploaded_file($tmp_name, $filePath);

            $pdo->prepare("INSERT INTO post_files (post_id, file_path) VALUES (?, ?)")->execute([$post_id, $filePath]);
        }
    }

    // Update post in database
    $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, location_name = ?, latitude = ?, longitude = ? WHERE id = ?");
    $stmt->execute([$title, $content, $location_name, $latitude, $longitude, $post_id]);

    // Track deleted tags
    $deletedTags = [];
    if (!empty($removedTagsInput)) {
        $deletedTags = array_map('trim', explode(',', $removedTagsInput));
        foreach ($deletedTags as $tag) {
            $stmt = $pdo->prepare("SELECT id FROM tags WHERE name = ?");
            $stmt->execute([$tag]);
            $tag_id = $stmt->fetchColumn();
            if ($tag_id) {
                $pdo->prepare("DELETE FROM post_tags WHERE post_id = ? AND tag_id = ?")->execute([$post_id, $tag_id]);
            }
        }
    }

    // Update tags
    $pdo->prepare("DELETE FROM post_tags WHERE post_id = ?")->execute([$post_id]);
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

    // Delete unused tags from tags table
    foreach ($deletedTags as $tag) {
        $stmt = $pdo->prepare("SELECT id FROM tags WHERE name = ?");
        $stmt->execute([$tag]);
        $tag_id = $stmt->fetchColumn();
        if ($tag_id) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM post_tags WHERE tag_id = ?");
            $stmt->execute([$tag_id]);
            $tagCount = $stmt->fetchColumn();
            if ($tagCount == 0) {
                $pdo->prepare("DELETE FROM tags WHERE id = ?")->execute([$tag_id]);
            }
        }
    }

    header("Location: index.php");
    exit;
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
        /* Container for the media gallery */
        .media-gallery {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        /* Individual media container */
        .media-container {
            position: relative;
            max-width: 150px;
            max-height: 150px;
            overflow: hidden;
        }

        .media-item {
            width: 100%;
            height: auto;
            display: block;
        }

        /* Remove button styling */
        .remove-button {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: rgba(220, 53, 69, 0.8); /* Bootstrap's danger color with transparency */
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 16px;
            width: 24px;
            height: 24px;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
        }

        /* Show remove button on hover */
        .media-container:hover .remove-button {
            display: flex;
        }

        /* Hide the checkbox */
        .remove-checkbox {
            display: none;
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
    <h2>Edit Post</h2>
    <form method="post" enctype="multipart/form-data">
        
        <!-- Title -->
        <div class="form-group">
            <label>Title:</label>
            <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
        </div>

        <!-- Content -->
        <div class="form-group">
            <label>Content:</label>
            <textarea name="content" id="content" rows="5"><?php echo htmlspecialchars($post['content']); ?></textarea>
            <small id="content-char-count">0/1000 characters used</small>
        </div>

        <!-- File Upload -->
        <div class="form-group">
            <label>Upload Media (leave empty to keep current):</label>
            <input type="file" name="images[]" id="file_input" multiple accept="image/*,video/*, audio/*">
            <div id="fileErrors" style="color: red; margin-top: 10px;"></div>
            <div id="filePreview" style="margin-top: 10px;"></div>
            <?php if (!empty($existingImages)): ?>
                <p><strong>Current Media:</strong></p>
                <div class="media-gallery">
                    <?php foreach ($existingImages as $image): ?>
                        <div class="media-container">
                            <?php if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $image)): ?>
                                <img src="<?php echo htmlspecialchars($image); ?>" class="media-item">
                            <?php elseif (preg_match('/\.(mp4|webm|mov)$/i', $image)): ?>
                                <video src="<?php echo htmlspecialchars($image); ?>" controls class="media-item"></video>
                            <?php elseif (preg_match('/\.(mp3|wav|ogg|m4a|flac)$/i', $image)): ?>
                                <audio src="<?php echo htmlspecialchars($image); ?>" controls class="media-item"></audio>
                            <?php endif; ?>
                            <button type="button" class="remove-button" onclick="removeMedia(this)">×</button>
                            <input type="checkbox" name="remove_images[]" value="<?php echo htmlspecialchars($image); ?>" class="remove-checkbox">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>

        <!-- Tags -->
        <div class="form-group">
            <label>Tags:</label>
            <input type="text" name="tags" value="<?php echo htmlspecialchars(getTags($tags, $post)); ?>" placeholder="e.g. travel, adventure, hiking">
        </div>

        <!-- Location Name -->
        <div class="form-group">
            <label>Location Name:</label>
            <input type="text" name="location_name" id="location_name" class="form-control" value="<?php echo htmlspecialchars($post['location_name']); ?>" placeholder="Enter a location name">
        </div>
        
        <!-- Location Selection -->
        <div class="form-group">
            <label>Select Location on Map:</label>
            <div id="map" style="height: 400px;"></div>
            <input type="hidden" name="latitude" id="latitude" value="<?php echo $post['latitude']; ?>">
            <input type="hidden" name="longitude" id="longitude" value="<?php echo $post['longitude']; ?>">
        </div>

        <div id="loading-indicator" class="loading-indicator">Fetching location...</div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-success" style="margin-top: 20px">Save Changes</button>
        <a href="index.php" class="btn btn-secondary" style="margin-top: 20px">Back to Home</a>
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
        var maxFileSize = 100 * 1024 * 1024; // MB per file
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
                fileErrorsDiv.innerHTML += `<p>Error: ${file.name} exceeds the MB limit.</p>`;
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

    // Form submit validation
    document.querySelector("form").addEventListener("submit", function(event) {
        var fileInput = document.getElementById("file_input");
        var existingMedia = document.querySelectorAll(".media-container .remove-checkbox:not(:checked)").length;
        var files = fileInput.files;
        var maxFiles = 10;
        
        // Check if the total number of files exceeds the maximum allowed
        if (files.length + existingMedia > maxFiles) {
            event.preventDefault(); // Prevent form submission
            var fileErrorsDiv = document.getElementById("fileErrors");
            fileErrorsDiv.innerHTML = `<p>Error: You can upload a maximum of ${maxFiles} files in total.</p>`;
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

    var map = L.map('map').setView([37.7749, -122.4194], 3);
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

    var latitude = <?php echo !empty($post['latitude']) ? $post['latitude'] : 'null'; ?>;
    var longitude = <?php echo !empty($post['longitude']) ? $post['longitude'] : 'null'; ?>;
    var marker;

    if (latitude !== null && longitude !== null) {
        marker = L.marker([latitude, longitude], { icon: greenIcon }).addTo(map);
        map.setView([latitude, longitude], 10);
        marker.bindPopup('<button onclick="removeMarker(event)">Remove Location</button>').openPopup();
    }

    function onMapClick(e) {
        if (marker) {
            map.removeLayer(marker);
        }
        marker = L.marker(e.latlng, { icon: greenIcon }).addTo(map);
        document.getElementById("latitude").value = e.latlng.lat;
        document.getElementById("longitude").value = e.latlng.lng;
        marker.bindPopup('<button onclick="removeMarker(event)">Remove Location</button>').openPopup();
    }

    map.on('click', onMapClick);

    function removeMarker(event) {
        event.preventDefault(); // Prevent form submission
        if (marker) {
            map.removeLayer(marker);
            marker = null;
            document.getElementById("latitude").value = '';
            document.getElementById("longitude").value = '';
        }
    }

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
        removedTagsInput.type = 'hidden';
        removedTagsInput.name = 'removed_tags';
        document.querySelector('form').appendChild(removedTagsInput);

        var tagify = new Tagify(input);

        tagify.on('remove', function(e) {
            var removedTag = e.detail.data.value;
            var removedTags = removedTagsInput.value ? removedTagsInput.value.split(',') : [];
            removedTags.push(removedTag);
            removedTagsInput.value = removedTags.join(',');
        });

        // Convert Tagify output to a simple comma-separated string before submitting the form
        document.querySelector('form').addEventListener('submit', function() {
            var tagsArray = tagify.value.map(tag => tag.value);
            input.value = tagsArray.join(',');
        });
    });
</script>
<script>
function removeMedia(button) {
    // Get the parent container
    var container = button.parentElement;
    // Find the hidden checkbox
    var checkbox = container.querySelector('.remove-checkbox');
    // Check the checkbox to mark for removal
    checkbox.checked = true;
    // Hide the container visually
    container.style.display = 'none';
}
</script>

<?php include 'footer.php'; ?>
