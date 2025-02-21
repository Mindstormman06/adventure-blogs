<?php
include 'db.php';
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data["remember_token"])) {
    echo json_encode(["status" => "error", "message" => "Token missing"]);
    exit;
}

$token = $data["remember_token"];

$stmt = $conn->prepare("SELECT * FROM users WHERE remember_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    // Generate JWT for mobile app authentication
    $jwt_payload = [
        "user_id" => $user["id"],
        "username" => $user["username"],
        "role" => $user["role"],
        "exp" => time() + 86400  // Token valid for 24 hours
    ];
    
    $jwt_secret = "testkey"; // Store this securely
    $jwt_token = base64_encode(json_encode($jwt_payload));

    echo json_encode([
        "status" => "success",
        "message" => "Login successful",
        "user" => [
            "id" => $user["id"],
            "username" => $user["username"],
            "role" => $user["role"]
        ],
        "token" => $jwt_token  // Send token to mobile app
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Invalid token"]);
}

$conn->close();
?>
