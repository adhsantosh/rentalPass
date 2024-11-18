<?php
session_start();
require 'database.php';

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle cancel request if submitted
if (isset($_POST['cancel_rental_id'])) {
    $cancelRentalId = $_POST['cancel_rental_id'];

    // Update the vehicle availability to 1 and set rental status to 'canceled'
    $stmt = $conn->prepare("
        UPDATE vehicles v 
        JOIN rentals r ON v.VID = r.VID 
        SET v.available = 1, r.status = 'canceled' 
        WHERE r.rental_id = ?
    ");
    $stmt->bind_param("i", $cancelRentalId);
    $stmt->execute();

    // Optionally, add a success message or refresh the page to update the list
}

// Fetch rental data from the database, including the vehicle availability status
$rentals = $conn->query("
    SELECT r.*, u.name AS user_name, v.name AS bike_name, r.status AS rental_status, v.available
    FROM rentals r 
    JOIN users u ON r.UID = u.UID 
    JOIN vehicles v ON r.VID = v.VID
    ORDER BY r.rental_start ASC
");

// Check if the query failed
if (!$rentals) {
    die('Query failed: ' . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Rentals</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 220px;
            background-color: #343a40;
            padding: 20px;
            overflow-y: auto;
        }
        .sidebar h3 {
            font-size: 1.2rem;
            font-weight: bold;
            color: #f8f9fa;
            text-align: center;
            margin-bottom: 20px;
        }
        .sidebar .nav-link {
            color: #ffffff;
            font-weight: 500;
        }
        .sidebar .nav-link:hover {
            background-color: #495057;
            border-radius: 5px;
        }
        .content {
            margin-left: 240px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h3>Admin Menu</h3>
        <nav class="nav flex-column">
            <a class="nav-link" href="manage_bicycle.php">Manage Bicycles</a>
            <a class="nav-link" href="manage_users.php">Manage Users</a>
            <a class="nav-link" href="view_rentals.php">View Rentals</a>
            <a class="nav-link" href="admin_logout.php">Logout</a>
        </nav>
    </div>
    <div class="content">
        <h1 class="text-center">View Rentals</h1>
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Rental History</h5>
                <p class="card-text">Here you can view all rental transactions and cancel if necessary.</p>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>User Name</th>
                                <th>Bicycle Name</th>
                                <th>Rental Start</th>
                                <th>Rental End</th>
                                <th>Rental Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($rental = $rentals->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($rental['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($rental['bike_name']); ?></td>
                                    <td><?php echo htmlspecialchars($rental['rental_start']); ?></td>
                                    <td><?php echo htmlspecialchars($rental['rental_end']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($rental['rental_status'])); ?></td>
                                    <td>
                                        <?php if ($rental['rental_status'] === 'active' && $rental['available'] == 0): ?>
                                            <form method="POST" style="display:inline;" onsubmit="disableButton(this);">
                                                <input type="hidden" name="cancel_rental_id" value="<?php echo htmlspecialchars($rental['rental_id']); ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Cancel Rental</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">Canceled</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.6/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        // JavaScript function to disable the button after submission
        function disableButton(form) {
            form.querySelector('button[type="submit"]').disabled = true;
        }
    </script>
</body>
</html>
