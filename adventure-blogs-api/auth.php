<?php
include 'db.php';
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

// Check if username and password are provided
if (!isset($data["username"]) || !isset($data["password"])) {
    echo json_encode(["status" => "error", "message" => "Username or password missing"]);
    exit;
}

$username = $data["username"];
$password = $data["password"]; // Plaintext password sent from mobile app

// Prepare the SQL query to find the user by username
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Check if user exists and verify password
if ($user && password_verify($password, $user["password_hash"])) {
    // Generate JWT for mobile app authentication
    $jwt_payload = [
        "user_id" => $user["id"],
        "username" => $user["username"],
        "role" => $user["role"],
        "exp" => time() + 86400  // Token valid for 24 hours
    ];

    $jwt_secret = "testkey"; // Store this securely (e.g., in an environment variable)
    // Encode JWT payload to create the token
    $jwt_token = base64_encode(json_encode($jwt_payload));

    // Return successful login response
    echo json_encode([
        "status" => "success",
        "message" => "Login successful",
        "user" => [
            "id" => $user["id"],
            "username" => $user["username"],
            "role" => $user["role"]
        ],
        // "token" => $jwt_token  // Send token to mobile app
    ]);
} else {
    // Return error if user not found or password doesn't match
    echo json_encode(["status" => "error", "message" => "Invalid username or password"]);
}

$conn->close();
