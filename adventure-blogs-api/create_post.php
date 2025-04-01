<?php
// header("Content-Type: multipart/form-data; charset=UTF-8");
var_dump($_POST);
var_dump($_FILES);

include 'db.php';
include 'verify_token.php'; // To verify the user's token

// Get token from request header and verify it
$headers = getallheaders();
if (!isset($headers["Authorization"])) {
    echo json_encode(["status" => "error", "message" => "No token provided"]);
    exit;
}

$token = str_replace("Bearer ", "", $headers["Authorization"]);
$user_data = verifyJWT($token);

if (!$user_data) {
    echo json_encode(["status" => "error", "message" => "Invalid or expired token"]);
    exit;
}

// Decode the JSON input
$data = $_POST;

// Check if JSON decoding failed
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(["status" => "error", "message" => "Invalid JSON input"]);
    exit;
}

// Check if required fields are provided
if (!isset($data["title"]) || !isset($data["content"])) {
    echo json_encode(["status" => "error", "message" => "Title or content missing"]);
    exit;
}

$title = $data["title"];
$content = $data["content"];
$author_id = $user_data["user_id"]; // Extracted from the verified token
$latitude = isset($data["latitude"]) ? $data["latitude"] : null;
$longitude = isset($data["longitude"]) ? $data["longitude"] : null;
$location_name = isset($data["location_name"]) ? $data["location_name"] : null;
$tags = isset($data["tags"]) ? $data["tags"] : []; // Array of tag names
$files = isset($_FILES["files"]) ? $_FILES["files"] : null;


// Insert the new post into the database
$stmt = $conn->prepare("INSERT INTO posts (user_id, title, content, created_at, latitude, longitude, location_name) VALUES (?, ?, ?, NOW(), ?, ?, ?)");
$stmt->bind_param("issdds", $author_id, $title, $content, $latitude, $longitude, $location_name);

if ($stmt->execute()) {
    $post_id = $stmt->insert_id;

    // Handle file uploads
    if ($files) {
        $upload_dir = "../uploads/";
        foreach ($files["name"] as $index => $filename) {
            $tmp_name = $files["tmp_name"][$index];

            // Extract the file extension
            $file_extension = pathinfo($filename, PATHINFO_EXTENSION);

            // Generate the custom file name
            $custom_file_name = $post_id . "-" . time() . "-" . $index . "." . $file_extension;
            $file_path = $upload_dir . $custom_file_name;

            // Move the uploaded file to the target directory
            if (move_uploaded_file($tmp_name, $file_path)) {
                $original_filename = $filename;

                // Insert the file details into the database
                $file_stmt = $conn->prepare("INSERT INTO post_files (post_id, file_path, original_filename) VALUES (?, ?, ?)");
                $file_stmt->bind_param("iss", $post_id, $file_path, $original_filename);
                $file_stmt->execute();
            }
        }
    }

    // Handle tags
    if (!is_array($tags)) {
        $tags = explode(",", $tags); // Convert comma-separated string to array
    }
    foreach ($tags as $key => $tag) {
        $tags[$key] = trim($tag); // Trim whitespace from each tag
    }
    if (empty($tags)) {
        $tags = []; // Ensure tags is an empty array if no tags are provided
    } else {
        $tags = array_unique($tags); // Remove duplicates
    }
    foreach ($tags as $tag_name) {
        // Check if the tag already exists
        $tag_stmt = $conn->prepare("SELECT id FROM tags WHERE name = ?");
        $tag_stmt->bind_param("s", $tag_name);
        $tag_stmt->execute();
        $tag_result = $tag_stmt->get_result();

        if ($tag_result->num_rows > 0) {
            $tag_id = $tag_result->fetch_assoc()["id"];
        } else {
            // Insert the new tag
            $insert_tag_stmt = $conn->prepare("INSERT INTO tags (name) VALUES (?)");
            $insert_tag_stmt->bind_param("s", $tag_name);
            $insert_tag_stmt->execute();
            $tag_id = $insert_tag_stmt->insert_id;
        }

        // Associate the tag with the post
        $post_tag_stmt = $conn->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)");
        $post_tag_stmt->bind_param("ii", $post_id, $tag_id);
        $post_tag_stmt->execute();
    }

    echo json_encode(["status" => "success", "message" => "Post created successfully", "post_id" => $post_id]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to create post"]);
}

$conn->close();