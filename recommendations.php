<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'database.php';
require 'recommendation_helper.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

$userId = $_SESSION['user_id'];

// Prepare matrices
list($twHybridRaw, $fwHybridRaw, $vehicles, $twCF, $fwCF) = prepareMatrices();

$pdo = getDatabaseConnection();

// Get last rented vehicles
$lastTWID = $pdo->query("SELECT TWID FROM rentals WHERE UID=$userId AND TWID IS NOT NULL ORDER BY rental_end DESC LIMIT 1")->fetchColumn();
$lastFWID = $pdo->query("SELECT FWID FROM rentals WHERE UID=$userId AND FWID IS NOT NULL ORDER BY rental_end DESC LIMIT 1")->fetchColumn();

// ------------------------ Two-Wheeler Recommendations ------------------------
$recommendTwHybrid = [];
$recommendTwCF = [];

if ($lastTWID) {
    // Hybrid top 2
    $recommendTwHybrid = recommend($lastTWID, $twHybridRaw, $vehicles, 2);

    // CF top 4
    $cfTop = recommend($lastTWID, $twCF, $vehicles, 4);
    foreach ($cfTop as $v) {
        // Remove from hybrid if also in CF
        foreach ($recommendTwHybrid as $key => $h) {
            if ($h['id'] == $v['id']) unset($recommendTwHybrid[$key]);
        }
        $recommendTwCF[] = $v;
        if (count($recommendTwCF) >= 3) break;
    }
}

// ------------------------ Four-Wheeler Recommendations ------------------------
$recommendFwHybrid = [];
$recommendFwCF = [];

if ($lastFWID) {
    // Hybrid top 2
    $recommendFwHybrid = recommend($lastFWID, $fwHybridRaw, $vehicles, 2);

    // CF top 4
    $cfTop = recommend($lastFWID, $fwCF, $vehicles, 4);
    foreach ($cfTop as $v) {
        foreach ($recommendFwHybrid as $key => $h) {
            if ($h['id'] == $v['id']) unset($recommendFwHybrid[$key]);
        }
        $recommendFwCF[] = $v;
        if (count($recommendFwCF) >= 3) break;
    }
}

// ------------------------ Send JSON ------------------------
echo json_encode([
    'two_wheeler' => ['hybrid' => array_values($recommendTwHybrid), 'cf' => $recommendTwCF],
    'four_wheeler' => ['hybrid' => array_values($recommendFwHybrid), 'cf' => $recommendFwCF]
]);
?>
