<?php
session_start();
require 'database.php'; // Ensure database connection is established

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get the vehicle ID from the URL parameter
if (isset($_GET['id'])) {
    $vehicleId = $_GET['id'];

    // Fetch vehicle details from the database
    $stmt = $conn->prepare("SELECT * FROM vehicles WHERE VID = ?");
    $stmt->bind_param("i", $vehicleId);
    $stmt->execute();
    $result = $stmt->get_result();
    $vehicle = $result->fetch_assoc();

    // Check if vehicle exists
    if (!$vehicle) {
        echo "Vehicle not found!";
        exit();
    }
} else {
    // If no vehicle ID is passed, redirect to the available bicycles page
    header("Location: view_bicycles.php");
    exit();
}

// Handle form submission to confirm rental
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_SESSION['user_id'];
    $vehicleId = $_POST['vehicle_id'];
    $rentalStart = $_POST['rental_start'];
    $rentalEnd = $_POST['rental_end'];

    // Prepare query to insert rental details into the database
    $stmt = $conn->prepare("INSERT INTO rentals (UID, VID, rental_start, rental_end) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $userId, $vehicleId, $rentalStart, $rentalEnd);

    // Debug: Check if prepare() was successful
    if (!$stmt) {
        die("Failed to prepare SQL statement: " . $conn->error);
    }

    // Execute query and check for success
    if ($stmt->execute()) {
        // Optionally, update the bicycle's availability to 0 (unavailable)
        $updateStmt = $conn->prepare("UPDATE vehicles SET available = 0 WHERE VID = ?");
        $updateStmt->bind_param("i", $vehicleId);
        
        // Debug: Check if update query preparation was successful
        if (!$updateStmt) {
            die("Failed to prepare UPDATE statement: " . $conn->error);
        }

        if ($updateStmt->execute()) {
            echo "Rental successful! The bicycle has been marked as unavailable.";
        } else {
            echo "Error: Failed to update bicycle availability. " . $updateStmt->error;
        }
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>