<?php
session_start();
require 'database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$vehicleType = $_GET['type'] ?? $_POST['vehicle_type'] ?? null;  // 'two' or 'four'
$vehicleId = $_GET['id'] ?? $_POST['vehicle_id'] ?? null;

if (!$vehicleType || !$vehicleId) {
    header("Location: index.php");
    exit();
}

// Determine table and ID column
if ($vehicleType === 'two') {
    $table = 'two_wheeler';
    $idCol = 'TWID';
} elseif ($vehicleType === 'four') {
    $table = 'four_wheeler';
    $idCol = 'FWID';
} else {
    die("Invalid vehicle type.");
}

// Fetch vehicle details
$stmt = $conn->prepare("SELECT * FROM $table WHERE $idCol = ? AND available = 1");
$stmt->bind_param("i", $vehicleId);
$stmt->execute();
$vehicle = $stmt->get_result()->fetch_assoc();

if (!$vehicle) {
    die("Vehicle not found or unavailable.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rentalStart = $_POST['rental_start'];
    $rentalEnd = $_POST['rental_end'];

    // Insert into rentals table accordingly
    if ($vehicleType === 'two') {
        $insert = $conn->prepare("INSERT INTO rentals (UID, TWID, rental_start, rental_end) VALUES (?, ?, ?, ?)");
        $insert->bind_param("iiss", $userId, $vehicleId, $rentalStart, $rentalEnd);
    } else {
        $insert = $conn->prepare("INSERT INTO rentals (UID, FWID, rental_start, rental_end) VALUES (?, ?, ?, ?)");
        $insert->bind_param("iiss", $userId, $vehicleId, $rentalStart, $rentalEnd);
    }

    if ($insert->execute()) {
        // Update availability
        $update = $conn->prepare("UPDATE $table SET available = 0 WHERE $idCol = ?");
        $update->bind_param("i", $vehicleId);
        $update->execute();

        echo "Rental successful!";
        header("Refresh:2; url=user_dashboard.php");
        exit();
    } else {
        echo "Error: " . $insert->error;
    }
}
?>