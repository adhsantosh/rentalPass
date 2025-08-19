<?php
session_start();
require 'database.php';

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

// Fetch vehicle details
$stmt = $conn->prepare("SELECT * FROM $table WHERE $idField = ? AND available = 1");
$stmt->bind_param("i", $vehicleId);
$stmt->execute();
$vehicle = $stmt->get_result()->fetch_assoc();

if (!$vehicle) {
    echo "<div class='alert alert-danger text-center mt-4'>This vehicle is not available!</div>";
    exit();
}

// Handle rental submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rentalStart = $_POST['rental_start'];
    $rentalEnd = $_POST['rental_end'];

    $insert = $conn->prepare("INSERT INTO rentals (UID, {$idField}, rental_start, rental_end, status) VALUES (?, ?, ?, ?, 'active')");
    $insert->bind_param("iiss", $userId, $vehicleId, $rentalStart, $rentalEnd);

    if ($insert->execute()) {
        $update = $conn->prepare("UPDATE $table SET available = 0 WHERE $idField = ?");
        $update->bind_param("i", $vehicleId);
        $update->execute();

        echo "<div class='alert alert-success text-center mt-4'>Rental successful! Redirecting...</div>";
        header("Refresh: 2; url=user_dashboard.php");
        exit();
    } else {
        echo "<div class='alert alert-danger text-center mt-4'>Rental failed: " . htmlspecialchars($insert->error) . "</div>";
    }
}

$photoPath = !empty($vehicle['photo']) ? htmlspecialchars($vehicle['photo']) : ($isTwoWheeler ? 'default_bike.png' : 'default_car.png');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Rent Vehicle</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />

       <style>
    body { background-color: #f8f9fa; }
    .container { max-width: 500px; margin-top: 50px; }
    .card { border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); padding: 20px; }
    .vehicle-img { width: 100%; max-height: 180px; object-fit:fill; border-radius: 12px 12px 0 0; margin-bottom: 15px; }
    .btn-primary { border-radius: 25px; width: 100%; padding: 8px 0; }
    </style>

</head>
<body>
    <div class="container">
        <div class="card p-4">
            <!-- Vehicle Image -->
            <img src="<?= $photoPath ?>" alt="<?= htmlspecialchars($vehicle['name']) ?>" class="vehicle-img">

            <h3 class="mb-3">Rent <?= htmlspecialchars($vehicle['name']) ?></h3>
            <p><strong>Model:</strong> <?= htmlspecialchars($vehicle['model']) ?></p>
            <p><strong>Price:</strong> Rs. <?= htmlspecialchars($vehicle['price']) ?> / day</p>

            <form method="POST">
                <div class="form-group">
        <label for="rental_start">Rental Start Date</label>
        <input type="date" id="rental_start" name="rental_start" class="form-control" 
               required min="<?= date('Y-m-d'); ?>">
    </div>

    <div class="form-group">
        <label for="rental_end">Rental End Date</label>
        <input type="date" id="rental_end" name="rental_end" class="form-control" 
               required min="<?= date('Y-m-d'); ?>">
    </div>

    <button type="submit" class="btn btn-primary mt-3">Confirm Rental</button>
            </form>
        </div>
    </div>
</body>
</html>
