<?php
session_start();
require 'database.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Determine vehicle type and ID
$twid = isset($_GET['twid']) ? (int)$_GET['twid'] : null;
$fwid = isset($_GET['fwid']) ? (int)$_GET['fwid'] : null;

if (!$twid && !$fwid) {
    echo "<div class='alert alert-danger text-center mt-4'>Invalid vehicle selection.</div>";
    exit();
}

$isTwoWheeler = $twid !== null;
$vehicleId = $isTwoWheeler ? $twid : $fwid;
$table = $isTwoWheeler ? "two_wheeler" : "four_wheeler";
$idField = $isTwoWheeler ? "TWID" : "FWID";

// Fetch vehicle
$stmt = $conn->prepare("SELECT * FROM $table WHERE $idField = ? AND available = 1");
$stmt->bind_param("i", $vehicleId);
$stmt->execute();
$vehicle = $stmt->get_result()->fetch_assoc();

if (!$vehicle) {
    echo "<div class='alert alert-danger text-center mt-4'>This vehicle is not available!</div>";
    exit();
}

// Handle rental form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rentalStart = $_POST['rental_start'];
    $rentalEnd = $_POST['rental_end'];

    $insert = $conn->prepare("INSERT INTO rentals (UID, {$idField}, rental_start, rental_end, status) VALUES (?, ?, ?, ?, 'active')");
    $insert->bind_param("iiss", $userId, $vehicleId, $rentalStart, $rentalEnd);

    if ($insert->execute()) {
        $update = $conn->prepare("UPDATE $table SET available = 0 WHERE $idField = ?");
        $update->bind_param("i", $vehicleId);
        $update->execute();
        echo "<div class='alert alert-success text-center mt-4'>Rental successful! Redirecting to your dashboard...</div>";
        header("Refresh: 2; url=user_dashboard.php");
        exit();
    } else {
        echo "<div class='alert alert-danger text-center mt-4'>Rental failed: " . htmlspecialchars($insert->error) . "</div>";
    }
}
?>
