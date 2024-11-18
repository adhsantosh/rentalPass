<?php
session_start();
require 'database.php';

if (!isset($_SESSION['user_id'])) {
    die("Access denied.");
}

$userId = $_SESSION['user_id'];

// Sample query to get rental recommendations based on collaborative filtering
// In a real application, implement collaborative filtering logic here
$result = $conn->query("SELECT * FROM vehicles WHERE available = 1 ORDER BY RAND() LIMIT 3");
$recommendations = [];

while ($row = $result->fetch_assoc()) {
    $recommendations[] = $row;
}

echo json_encode($recommendations);
?>