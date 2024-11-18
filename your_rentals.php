<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    exit("You must be logged in to access this page.");
}

require 'database.php';
$user_id = $_SESSION['user_id'];

// Handle rental cancellation
if (isset($_POST['cancel_rental_id'])) {
    $cancelRentalId = $_POST['cancel_rental_id'];

    // Verify if the rental belongs to the logged-in user before proceeding with cancellation
    $stmt = $conn->prepare("SELECT VID FROM rentals WHERE rental_id = ? AND UID = ?");
    $stmt->bind_param("ii", $cancelRentalId, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // If the rental exists and belongs to the user, proceed with cancellation
    if ($result->num_rows > 0) {
        $vehicle = $result->fetch_assoc();
        $vehicleId = $vehicle['VID'];

        // Update rental status to 'canceled'
        $stmt = $conn->prepare("UPDATE rentals SET status = 'canceled' WHERE rental_id = ?");
        $stmt->bind_param("i", $cancelRentalId);
        $stmt->execute();

        // Set the vehicle as available
        $stmt = $conn->prepare("UPDATE vehicles SET available = 1 WHERE VID = ?");
        $stmt->bind_param("i", $vehicleId);
        $stmt->execute();

        // Redirect after successful cancellation
        header("Location: your_rentals.php");
        exit();
    } else {
        echo "<div class='alert alert-danger'>Unauthorized cancellation attempt or rental not found.</div>";
    }
}

// Query to retrieve active rentals for the logged-in user, ordered by rental_start DESC
$stmt = $conn->prepare("
    SELECT r.rental_id, v.name, v.model, v.price, r.rental_start, r.rental_end, r.status AS rental_status
    FROM rentals r 
    JOIN vehicles v ON r.VID = v.VID
    WHERE r.UID = ? 
    ORDER BY r.rental_start DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Rentals</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            height: 100vh;
            background-color: #343a40;
            padding: 20px;
        }
        .sidebar a {
            color: #ffffff;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar for user dashboard -->
        <div class="sidebar">
            <h3 class="text-white text-center">User Menu</h3>
            <nav class="nav flex-column">
                <a class="nav-link" href="user_dashboard.php">Dashboard</a>
                <a class="nav-link" href="view_bicycles.php">View Bicycles</a>
                <a class="nav-link" href="your_rentals.php">Your Rentals</a>
                <a class="nav-link" href="edit_profile.php">Edit Profile</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </nav>
        </div>
        
        <div class="content">
            <h1>Your Rentals</h1>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Bicycle Name</th>
                            <th>Model</th>
                            <th>Price</th>
                            <th>Rental Start</th>
                            <th>Rental End</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result->num_rows > 0) {
                            while ($rental = $result->fetch_assoc()) {
                                echo "<tr>
                                        <td>" . htmlspecialchars($rental['name']) . "</td>
                                        <td>" . htmlspecialchars($rental['model']) . "</td>
                                        <td>" . htmlspecialchars($rental['price']) . "</td>
                                        <td>" . htmlspecialchars($rental['rental_start']) . "</td>
                                        <td>" . htmlspecialchars($rental['rental_end']) . "</td>
                                        <td>" . ucfirst($rental['rental_status']) . "</td>
                                        <td>";

                                // Show cancel button only if rental is active
                                if ($rental['rental_status'] === 'active') {
                                    echo "<form method='POST'>
                                            <input type='hidden' name='cancel_rental_id' value='" . $rental['rental_id'] . "'>
                                            <button type='submit' class='btn btn-danger btn-sm'>Cancel Rental</button>
                                          </form>";
                                } else {
                                    echo "<span class='text-muted'>N/A</span>";
                                }

                                echo "</td>
                                      </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7'>You have no active rentals.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.6/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
