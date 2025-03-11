<?php
require 'config.php';

try {
    $stmt = $pdo->prepare("DELETE FROM users WHERE verified = 0");
    $stmt->execute();

    echo "Cleanup completed. Removed " . $stmt->rowCount() . " unverified users.\n";
} catch (PDOException $e) {
    echo "Error: ". $e->getMessage();
}