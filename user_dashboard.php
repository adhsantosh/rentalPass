<?php
session_start();
require 'database.php';
require 'recommendation_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $conn->prepare("SELECT * FROM users WHERE UID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get similarity matrices and recommendations
$twMatrix = getSimilarityMatrix($conn, 'TWID');
$fwMatrix = getSimilarityMatrix($conn, 'FWID');

$twRecommendations = getRecommendations($conn, $user_id, $twMatrix, 'TWID');
$fwRecommendations = getRecommendations($conn, $user_id, $fwMatrix, 'FWID');

// Fetch two-wheeler recommendation details
$twoWheelers = [];
if (!empty($twRecommendations)) {
    $in = str_repeat('?,', count($twRecommendations) - 1) . '?';
    $stmt = $conn->prepare("SELECT * FROM two_wheeler WHERE TWID IN ($in)");
    $stmt->bind_param(str_repeat("i", count($twRecommendations)), ...$twRecommendations);
    $stmt->execute();
    $twoWheelers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Fetch four-wheeler recommendation details
$fourWheelers = [];
if (!empty($fwRecommendations)) {
    $in = str_repeat('?,', count($fwRecommendations) - 1) . '?';
    $stmt = $conn->prepare("SELECT * FROM four_wheeler WHERE FWID IN ($in)");
    $stmt->bind_param(str_repeat("i", count($fwRecommendations)), ...$fwRecommendations);
    $stmt->execute();
    $fourWheelers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; }
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
            margin-left: 150px;
            padding: 20px;
        }
        .card-img-top {
            height: 180px;
            object-fit: cover;
        }
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
        <h1 class="text-center">Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h1>

        <!-- Profile Info -->
        <div class="row">
            <div class="col-md-6">
                <div class="card mt-3">
                    <div class="card-header bg-primary text-white">Your Profile</div>
                    <div class="card-body">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($user['address']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Recent Rentals -->
            <div class="col-md-6">
                <div class="card mt-3">
                    <div class="card-header bg-warning text-white">Recent Rentals</div>
                    <div class="card-body">
                        <ul class="list-group">
                            <?php
                            $rentalStmt = $conn->prepare("SELECT * FROM rentals WHERE UID = ? ORDER BY rental_start DESC LIMIT 5");
                            $rentalStmt->bind_param("i", $user_id);
                            $rentalStmt->execute();
                            $rentalResult = $rentalStmt->get_result();

                            if ($rentalResult->num_rows > 0) {
                                while ($r = $rentalResult->fetch_assoc()) {
                                    $type = $r['TWID'] ? "Two-Wheeler ID: " . $r['TWID'] : "Four-Wheeler ID: " . $r['FWID'];
                                    echo "<li class='list-group-item'>" . $type . " | Rented on: " . $r['rental_start'] . "</li>";
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

        <!-- Recommendations -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-success text-white">Recommended Two-Wheelers for You</div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($twoWheelers as $tw): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card">
                                        <img src="<?php echo htmlspecialchars($tw['photo']); ?>" class="card-img-top" alt="Bike">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($tw['name']); ?></h5>
                                            <p><?php echo htmlspecialchars($tw['model']); ?></p>
                                            <p><strong>Price:</strong> <?php echo htmlspecialchars($tw['price']); ?></p>
                                            <a href="rent_twoWheeler.php?id=<?php echo $tw['TWID']; ?>" class="btn btn-primary">Rent</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($twoWheelers)) echo "<p class='pl-3'>No two-wheeler recommendations available.</p>"; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-12 mt-4">
                <div class="card">
                    <div class="card-header bg-info text-white">Recommended Four-Wheelers for You</div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($fourWheelers as $fw): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card">
                                        <img src="<?php echo htmlspecialchars($fw['photo']); ?>" class="card-img-top" alt="Car">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($fw['name']); ?></h5>
                                            <p><?php echo htmlspecialchars($fw['model']); ?></p>
                                            <p><strong>Price:</strong> <?php echo htmlspecialchars($fw['price']); ?></p>
                                            <a href="rent_fourWheeler.php?id=<?php echo $fw['FWID']; ?>" class="btn btn-primary">Rent</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($fourWheelers)) echo "<p class='pl-3'>No four-wheeler recommendations available.</p>"; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- end content -->
</div><!-- end flex -->

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
