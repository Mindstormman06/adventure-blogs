<?php
$jwt_secret = "testkey"; // Use the same key as in auth.php

function verifyJWT($token)
{
    $decoded = json_decode(base64_decode($token), true);

    if (!$decoded || !isset($decoded["exp"]) || $decoded["exp"] < time()) {
        return null; // Token is expired or invalid
    }

    return $decoded; // Token is valid
}

// Get token from request header
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
