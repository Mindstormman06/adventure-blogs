<?php

ob_start();

include 'config.php';
include 'auth.php';
include 'header.php';

require 'vendor\erusev\parsedown\Parsedown.php'; // Include Parsedown for Markdown support
require_once 'vendor/autoload.php'; // Include Composer autoload
require 'models/Post.php'; // Include the Post class

if ($user['role'] !== 'user' && $user['role'] !== 'admin') {
    die("Access denied. Only registered users can create posts.");
}


// Configure HTMLPurifier
$config = HTMLPurifier_Config::createDefault();
$purifier = new HTMLPurifier($config);

// Initialize Parsedown
$Parsedown = new Parsedown(); // Initialize Parsedown

// Create Post object
$postObj = new Post($pdo, $Parsedown, $purifier);

// Check if user is an admin or user
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);


// Handle Post Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST["title"]);
    $content = trim($_POST["content"]);
    $user_id = $_SESSION["user_id"];
    $latitude = !empty($_POST["latitude"]) ? $_POST["latitude"] : null;
    $longitude = !empty($_POST["longitude"]) ? $_POST["longitude"] : null;
    $tagsInput = trim($_POST["tags"]);
    $location_name = isset($latitude) && isset($longitude) ? (!empty($_POST['location_name']) ? $_POST['location_name'] : "Tagged Location") : null;

    // Validate title and content
    if (empty($title)) {
        echo "<p>Error: Title must be filled in.</p>";
    } elseif (strlen($content) > 1000) {
        echo "<p>Error: Content exceeds the 1000-character limit.</p>";
    } else {
        try {
            // Create post
            $post_id = $postObj->createPost($user_id, $title, $content, $location_name, $latitude, $longitude);

            // Handle file uploads
            if (!empty($_FILES["images"]["name"][0])) {
                $postObj->uploadFiles($post_id, $_FILES["images"]);
            }

            // Add tags
            if (!empty($tagsInput)) {
                $postObj->addTags($post_id, $tagsInput);
            }

            header("Location: index.php");
            exit;
        } catch (Exception $e) {
            echo "<p>Error: " . $e->getMessage() . "</p>";
        }
    }
}
?>

<head>
    <style>
        /* Tagify input field styling */
        .tagify__input {
            min-width: 150px;
            /* Adjust width as needed */
            padding-left: 10px;
            padding-right: 10px;
            display: inline-block;
            /* Ensure it's displayed inline-block */
            vertical-align: middle;
            /* Align it in the middle */
        }

        .tagify {
            display: flex;
            align-items: center;
            min-height: 38px;
            /* Ensuring the container has a consistent height */
            border: 1px solid #ccc;
            /* Adding border for a complete input field appearance */
            padding: 5px;
            /* Adding padding for a consistent look */
        }

        /* Placeholder text styling */
        .tagify__input::placeholder {
            opacity: 0.5;
            /* Adjust opacity for better visibility */
            padding-left: 10px;
            /* Ensure placeholder is well-aligned */
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
<div class="container">
    <h2>Create a New Post</h2>
    <form method="post" enctype="multipart/form-data" onsubmit="return postHandler.validatePost()">

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

<!-- Pass data to JavaScript -->
<script>
    let latitude = 37.7749; // Default latitude
    let longitude = -122.4194; // Default longitude
</script>
<script src="js/PostHandler.js"></script>
<script src="js/CreatePostMapHandler.js"></script>

<?php include 'footer.php'; ?>

<?php ob_end_flush(); ?>