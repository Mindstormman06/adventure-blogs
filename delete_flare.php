<?php
session_start();
include 'config.php';

$data = json_decode(file_get_contents('php://input'), true);
$flareId = $data['id'];

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$stmt = $pdo->prepare("SELECT user FROM flare_posts WHERE id = ?");
$stmt->execute([$flareId]);
$flare = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$flare) {
    echo json_encode(['success' => false, 'message' => 'Flare not found']);
    exit();
}

$user = $_SESSION['username'];
$isAdmin = $_SESSION['role'] === 'admin';

if ($flare['user'] !== $user && !$isAdmin) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$stmt = $pdo->prepare("DELETE FROM flare_posts WHERE id = ?");
$stmt->execute([$flareId]);

echo json_encode(['success' => true]);
