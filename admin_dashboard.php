<?php
session_start();
require 'database.php'; // Assumes MySQLi connection as $conn
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Database connection (fallback to PDO if needed)
function getDatabaseConnection() {
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=rental_pass", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Demand Prediction: Simple Linear Regression
function predictDemand($pdo) {
    // Fetch rental data (count rentals per day of week)
    $sql = "SELECT DAYOFWEEK(rental_start) as day_of_week, COUNT(*) as rental_count
            FROM rentals
            WHERE rental_start IS NOT NULL
            GROUP BY day_of_week";
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare training data (day_of_week: 1=Sunday, 2=Monday, ..., 7=Saturday)
    $X = []; // Day of week (1-7)
    $y = []; // Rental counts
    foreach ($data as $row) {
        $X[] = $row['day_of_week'];
        $y[] = $row['rental_count'];
    }

    // Simple Linear Regression: y = mx + b
    $n = count($X);
    if ($n < 2) return []; // Not enough data for regression

    $sum_x = array_sum($X);
    $sum_y = array_sum($y);
    $sum_xy = 0;
    $sum_xx = 0;
    for ($i = 0; $i < $n; $i++) {
        $sum_xy += $X[$i] * $y[$i];
        $sum_xx += $X[$i] * $X[$i];
    }

    // Calculate slope (m) and intercept (b)
    $m = ($n * $sum_xy - $sum_x * $sum_y) / ($n * $sum_xx - $sum_x * $sum_x);
    $b = ($sum_y - $m * $sum_x) / $n;

    // Predict demand for next 7 days
    $predictions = [];
    $today = new DateTime('2025-08-19'); // Current date
    $threshold = 2; // Assume high demand if predicted count > 2 (adjust based on data)
    for ($i = 0; $i < 7; $i++) {
        $date = clone $today;
        $date->modify("+$i days");
        $day_of_week = (int)$date->format('N') + 1; // 1=Sunday, ..., 7=Saturday
        $predicted_count = max(0, $m * $day_of_week + $b); // Predicted rentals
        $predictions[] = [
            'date' => $date->format('Y-m-d'),
            'day' => $date->format('l'),
            'predicted_count' => round($predicted_count, 1),
            'high_demand' => $predicted_count > $threshold ? 'High Demand Expected' : 'Normal Demand'
        ];
    }
    return $predictions;
}

// Fraud/Anomaly Detection: Rule-Based
function detectAnomalies($pdo) {
    $anomalies = [];
    
    // Rule 1: Multiple bookings by the same user within 24 hours
    $sql = "SELECT UID, COUNT(*) as booking_count, MIN(rental_start) as first_booking, MAX(rental_start) as last_booking
            FROM rentals
            WHERE rental_start >= NOW() - INTERVAL 1 DAY
            GROUP BY UID
            HAVING booking_count > 3"; // Threshold: More than 3 bookings in 24 hours
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as $row) {
        $anomalies[] = "User ID {$row['UID']} made {$row['booking_count']} bookings between {$row['first_booking']} and {$row['last_booking']}.";
    }

    // Rule 2: User booking multiple vehicles at once
    $sql = "SELECT UID, rental_start, COUNT(*) as vehicle_count
            FROM rentals
            WHERE status = 'active'
            GROUP BY UID, rental_start
            HAVING vehicle_count > 2"; // Threshold: More than 2 vehicles in one booking
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as $row) {
        $anomalies[] = "User ID {$row['UID']} booked {$row['vehicle_count']} vehicles on {$row['rental_start']}.";
    }

    return $anomalies;
}

$pdo = getDatabaseConnection();
$demandPredictions = predictDemand($pdo);
$anomalies = detectAnomalies($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
            background-color: #343a40;
            padding: 20px;
            overflow-y: auto;
        }
        .sidebar a {
            color: #ffffff;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .sidebar .active {
            background-color: #007bff;
            font-weight: bold;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
        }
        .card {
            margin-top: 20px;
        }
        .high-demand {
            color: #dc3545;
            font-weight: bold;
        }
        .alert {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h3 class="text-white text-center">Admin Menu</h3>
        <nav class="nav flex-column">
            <a class="nav-link active" href="admin_dashboard.php">Admin Dashboard</a>
            <a class="nav-link" href="manage_twoWheeler.php">Manage Two-Wheeler</a>
            <a class="nav-link" href="manage_fourWheeler.php">Manage Four-Wheeler</a>
            <a class="nav-link" href="manage_users.php">Manage Users</a>
            <a class="nav-link" href="view_rentals.php">View Rentals</a>
            <a class="nav-link" href="admin_logout.php">Logout</a>
        </nav>
    </div>
    <div class="content">
        <h1 class="text-center">Admin Dashboard</h1>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Welcome to the Admin Dashboard</h5>
                <p class="card-text">Here you can manage vehicles, users, and rentals effectively.</p>
            </div>
        </div>

        <!-- Demand Prediction -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Demand Prediction (Next 7 Days)</h5>
                <?php if (empty($demandPredictions)): ?>
                    <p class="card-text">Not enough data to predict demand.</p>
                <?php else: ?>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Day</th>
                                <th>Predicted Rentals</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($demandPredictions as $prediction): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($prediction['date']); ?></td>
                                    <td><?php echo htmlspecialchars($prediction['day']); ?></td>
                                    <td><?php echo htmlspecialchars($prediction['predicted_count']); ?></td>
                                    <td class="<?php echo $prediction['high_demand'] === 'High Demand Expected' ? 'high-direct' : ''; ?>">
                                        <?php echo htmlspecialchars($prediction['high_demand']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Fraud/Anomaly Detection -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Fraud/Anomaly Detection</h5>
                <?php if (empty($anomalies)): ?>
                    <p class="card-text">No suspicious activities detected.</p>
                <?php else: ?>
                    <?php foreach ($anomalies as $anomaly): ?>
                        <div class="alert alert-warning">
                            <?php echo htmlspecialchars($anomaly); ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.6/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>