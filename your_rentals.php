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

    // Get TWID or FWID for that rental
    $stmt = $conn->prepare("SELECT TWID, FWID FROM rentals WHERE rental_id = ? AND UID = ?");
    $stmt->bind_param("ii", $cancelRentalId, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $rental = $result->fetch_assoc();

        // Cancel rental
        $stmt = $conn->prepare("UPDATE rentals SET status = 'canceled' WHERE rental_id = ?");
        $stmt->bind_param("i", $cancelRentalId);
        $stmt->execute();

        // Update availability based on vehicle type
        if ($rental['TWID']) {
            $update = $conn->prepare("UPDATE two_wheeler SET available = 1 WHERE TWID = ?");
            $update->bind_param("i", $rental['TWID']);
            $update->execute();
        } elseif ($rental['FWID']) {
            $update = $conn->prepare("UPDATE four_wheeler SET available = 1 WHERE FWID = ?");
            $update->bind_param("i", $rental['FWID']);
            $update->execute();
        }

        header("Location: your_rentals.php");
        exit();
    } else {
        echo "<div class='alert alert-danger'>Unauthorized cancellation attempt or rental not found.</div>";
    }
}

// Fetch all rentals for user
$query = "
    SELECT r.rental_id, 
           COALESCE(t.name, f.name) AS name, 
           COALESCE(t.model, f.model) AS model, 
           COALESCE(t.price, f.price) AS price,
           r.rental_start, r.rental_end, r.status AS rental_status
    FROM rentals r
    LEFT JOIN two_wheeler t ON r.TWID = t.TWID
    LEFT JOIN four_wheeler f ON r.FWID = f.FWID
    WHERE r.UID = ?
    ORDER BY r.rental_start DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Rentals</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar {
            height: 100vh;
            width: 250px;
            background-color: #2c3e50; /* Dark blue-grey background */
            color: #fff;
            flex-shrink: 0; /* Prevents the sidebar from shrinking */
        }
        .sidebar h3 {
            padding: 20px;
            margin: 0;
            background-color: #233140;
        }
        .sidebar .nav-link {
            color: #ecf0f1; /* Light grey text */
            padding: 15px 20px;
            text-decoration: none;
            display: block;
            border-bottom: 1px solid #34495e; /* Separator line */
            transition: background-color 0.3s;
        }
        .sidebar .nav-link:hover {
            background-color: #34495e; /* Slightly lighter on hover */
        }
        .content { margin-left: 100px; padding: 20px; }
        .content h1 { text-align: center; margin-bottom: 30px; }
        table th, table td { vertical-align: middle !important; }
    </style>
</head>
<body>
<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar">
        <h3 class="text-white text-center">User Menu</h3>
        <nav class="nav flex-column">
            <a class="nav-link" href="user_dashboard.php">Dashboard</a>
            <a class="nav-link" href="view_twoWheeler.php">View Bikes</a>
            <a class="nav-link" href="view_fourWheeler.php">View Cars</a>
            <a class="nav-link" href="your_rentals.php">Your Rentals</a>
            <a class="nav-link" href="edit_profile.php">Edit Profile</a>
            <a class="nav-link" href="logout.php">Logout</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="content">
        <h1>Your Rentals</h1>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="thead-dark">
                <tr>
                    <th>Vehicle Name</th>
                    <th>Model</th>
                    <th>Price</th>
                    <th>Rental Start</th>
                    <th>Rental End</th>
                    <th>Status</th>
                    <th>Action</th>
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
                        if ($rental['rental_status'] === 'active') {
                            echo "<form method='POST' style='margin:0;'>
                                    <input type='hidden' name='cancel_rental_id' value='" . $rental['rental_id'] . "'>
                                    <button type='submit' class='btn btn-danger btn-sm'>Cancel</button>
                                  </form>";
                        } else {
                            echo "<span class='text-muted'>N/A</span>";
                        }
                        echo "</td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' class='text-center'>No rentals found.</td></tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
