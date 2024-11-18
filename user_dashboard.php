<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'database.php';
require 'recommendation_helper.php';

$user_id = $_SESSION['user_id'];

// Get similarity matrix using collaborative filtering algorithm
$similarityMatrix = getBicycleSimilarityMatrix($conn);
$recommendedBicycles = getRecommendedBicycles($conn, $user_id, $similarityMatrix);

// Fetch user information
$stmt = $conn->prepare("SELECT * FROM users WHERE UID = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa; /* Light background */
        }
        .sidebar {
            height: 100vh; /* Full height */
            background-color: #343a40; /* Dark background for sidebar */
            padding: 20px;
        }
        .sidebar a {
            color: #ffffff; /* White text for sidebar links */
        }
        .sidebar a:hover {
            background-color: #495057; /* Hover effect */
        }
        .content {
            margin-left: 250px; /* Space for the sidebar */
            padding: 20px;
        }
        .card {
            margin-top: 20px; /* Space between cards */
        }
    </style>
</head>
<body>
    <div class="d-flex">
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
            <h1 class="text-center">Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h1>
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Your Profile Information</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
                            <p><strong>Address:</strong> <?php echo htmlspecialchars($user['address']); ?></p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0">Your Recent Rentals</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group">
                                <?php
                                // Fetch user's rental history
                                $rentalStmt = $conn->prepare("SELECT * FROM rentals WHERE UID = ?");
                                $rentalStmt->bind_param("s", $user_id);
                                $rentalStmt->execute();
                                $rentalResult = $rentalStmt->get_result();

                                if ($rentalResult->num_rows > 0) {
                                    while ($rental = $rentalResult->fetch_assoc()) {
                                        echo "<li class='list-group-item'>Bicycle ID: " . htmlspecialchars($rental['VID']) . " - Rented on: " . htmlspecialchars($rental['rental_start']) . "</li>";
                                    }
                                } else {
                                    echo "<li class='list-group-item'>No recent rentals.</li>";
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recommendations Section -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Recommended Bicycles for You</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php
                                if (!empty($recommendedBicycles)) {
                                    // Fetch recommended bicycles details
                                    $placeholders = implode(',', array_fill(0, count($recommendedBicycles), '?'));
                                    $stmt = $conn->prepare("SELECT * FROM vehicles WHERE VID IN ($placeholders)");
                                    $stmt->bind_param(str_repeat("i", count($recommendedBicycles)), ...$recommendedBicycles);
                                    $stmt->execute();
                                    $result = $stmt->get_result();

                                    while ($bicycle = $result->fetch_assoc()) {
                                        echo "<div class='col-md-4 mb-4'>";
                                        echo "<div class='card'>";
                                        echo "<img src='" . htmlspecialchars($bicycle['photo']) . "' class='card-img-top' alt='Bicycle Image'>";
                                        echo "<div class='card-body'>";
                                        echo "<h5 class='card-title'>" . htmlspecialchars($bicycle['name']) . "</h5>";
                                        echo "<p>" . htmlspecialchars($bicycle['model']) . "</p>";
                                        echo "<p><strong>Size:</strong> " . htmlspecialchars($bicycle['price']) . "</p>";
                                        echo "<a href='rent_bicycle.php?id=" . htmlspecialchars($bicycle['VID']) . "' class='btn btn-primary'>Rent Now</a>";
                                        echo "</div></div></div>";
                                    }
                                } else {
                                    echo "<p>No recommendations available at this time.</p>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.6/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
