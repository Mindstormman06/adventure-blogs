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

    header("Location: index.php");
    exit;
}
?>

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
            <textarea name="content" id="content" rows="5" required><?php echo htmlspecialchars($post['content']); ?></textarea>
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
                <div class="d-flex flex-wrap gap-3">
                    <?php foreach ($existingImages as $image): ?>
                        <div class="text-center" style="max-width: 150px;">
                            <?php if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $image)): ?>
                                <img src="<?php echo $image; ?>" class="img-thumbnail mb-2" style="max-width: 100%;">
                            <?php elseif (preg_match('/\.(mp4|webm|mov)$/i', $image)): ?>
                                <video src="<?php echo $image; ?>" controls class="img-thumbnail mb-2" style="max-width: 100%;">
                                </video>
                            <?php elseif (preg_match('/\.(mp3|wav|ogg|mp4|m4a|flac)$/i', $image)): ?>
                                <audio src="<?php echo $image; ?>" controls class="mb-2" style="width: 100%;">
                                </audio>
                            <?php endif; ?>
                            <div>
                                <input type="checkbox" name="remove_images[]" value="<?php echo $image; ?>" id="remove_<?php echo $image; ?>">
                                <label for="remove_<?php echo $image; ?>">Remove</label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>

        <!-- Tags -->
        <div class="form-group">
            <label>Tags (comma-separated):</label>
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

        if (title.trim() === "" || content.trim() === "") {
            alert("Both the title and content must be filled in.");
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

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    var latitude = <?php echo !empty($post['latitude']) ? $post['latitude'] : 'null'; ?>;
    var longitude = <?php echo !empty($post['longitude']) ? $post['longitude'] : 'null'; ?>;
    var marker;

    if (latitude !== null && longitude !== null) {
        marker = L.marker([latitude, longitude]).addTo(map);
        map.setView([latitude, longitude], 10);
    }

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
