<?php
session_start();
require 'database.php';
require 'recommendation_helper.php';

if (!isset($_SESSION['user_id'])) {
    die("Access denied.");
}

$userId = $_SESSION['user_id'];

// Generate two-wheeler recommendations
$twMatrix = getSimilarityMatrix($conn, "TWID");
$twRecommendations = getRecommendations($conn, $userId, $twMatrix, "TWID");

// Generate four-wheeler recommendations
$fwMatrix = getSimilarityMatrix($conn, "FWID");
$fwRecommendations = getRecommendations($conn, $userId, $fwMatrix, "FWID");

echo json_encode([
    'two_wheeler' => $twRecommendations,
    'four_wheeler' => $fwRecommendations
]);
?>
