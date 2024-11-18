<?php
session_start();
require 'database.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$vehicleId = isset($_GET['id']) ? $_GET['id'] : $_POST['vehicle_id'];

// Fetch vehicle details and rental status
$stmt = $conn->prepare("
    SELECT * FROM vehicles v 
    LEFT JOIN rentals r ON v.VID = r.VID AND r.rental_end > NOW()
    WHERE v.VID = ? AND v.available = 1 AND (r.UID IS NULL OR r.UID = ?)");
$stmt->bind_param("ii", $vehicleId, $userId);
$stmt->execute();
$vehicle = $stmt->get_result()->fetch_assoc();

if (!$vehicle) {
    echo "<div class='alert alert-danger text-center mt-4'>This vehicle is either currently rented by another user or unavailable!</div>";
    exit();
}

// Handle form submission for rental
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rentalStart = $_POST['rental_start'];
    $rentalEnd = $_POST['rental_end'];

    // Add rental and update availability
    $stmt = $conn->prepare("INSERT INTO rentals (UID, VID, rental_start, rental_end) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $userId, $vehicleId, $rentalStart, $rentalEnd);
    
    if ($stmt->execute()) {
        $updateStmt = $conn->prepare("UPDATE vehicles SET available = 0 WHERE VID = ?");
        $updateStmt->bind_param("i", $vehicleId);
        $updateStmt->execute();
        echo "<div class='alert alert-success text-center mt-4'>Rental successful! Redirecting to your dashboard...</div>";
        header("Refresh: 2; url=user_dashboard.php");
    } else {
        echo "<div class='alert alert-danger text-center mt-4'>Error: " . htmlspecialchars($stmt->error) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rent Bicycle - Rental Pass</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f7f9fb;
            color: #343a40;
            font-family: Arial, sans-serif;
        }
        .navbar {
            background-color: #3a6ea5;
        }
        .navbar a {
            color: #ffffff;
        }
        .navbar a:hover {
            color: #d9e8f6;
        }
        .container {
            margin-top: 50px;
            max-width: 600px;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .form-title {
            text-align: center;
            font-size: 1.5rem;
            color: #3a6ea5;
            margin-bottom: 20px;
        }
        .form-group label {
            font-weight: bold;
            color: #555;
        }
        .btn-primary {
            background-color: #3a6ea5;
            border: none;
            width: 100%;
            padding: 10px;
            font-size: 1rem;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #325f87;
        }
        .bike-info {
            text-align: center;
            margin-bottom: 20px;
        }
        .bike-info h2 {
            color: #343a40;
            font-weight: bold;
            font-size: 1.2rem;
        }
        .bike-info p {
            font-size: 0.9rem;
            color: #777;
        }
        .bike-details {
            font-size: 0.9rem;
            color: #555;
            margin-top: 15px;
        }
        .bike-details p {
            margin-bottom: 5px;
        }
        .form-group input[type="datetime-local"] {
            font-size: 0.9rem;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .price-calculation {
            margin-top: 20px;
            text-align: center;
            font-size: 1rem;
            font-weight: bold;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light">
    <a class="navbar-brand" href="index.php">Rental Pass</a>
    <div class="collapse navbar-collapse">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item"><a class="nav-link" href="user_dashboard.php">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
        </ul>
    </div>
</nav>

<div class="container">
    <div class="form-title">Confirm Your Rental</div>
    
    <div class="bike-info">
        <h2><?php echo htmlspecialchars($vehicle['name']); ?> - <?php echo htmlspecialchars($vehicle['model']); ?></h2>
        <p>Rent this bike and enjoy your journey!</p>
    </div>

    <!-- Bicycle Details Section -->
    <div class="bike-details">
        <p><strong>Price:</strong> Rs. <?php echo htmlspecialchars($vehicle['price']); ?> per day</p>
        <!-- <p><strong>Size:</strong> <?php echo htmlspecialchars($vehicle['size']); ?></p> -->
        <!-- <p><strong>Description:</strong> <?php echo htmlspecialchars($vehicle['description']); ?></p> -->
        <!-- <p><strong>Features:</strong> <?php echo htmlspecialchars($vehicle['features']); ?></p> -->
    </div>

    <!-- Rental Form -->
    <form method="POST" action="">
        <input type="hidden" name="vehicle_id" value="<?php echo htmlspecialchars($vehicle['VID']); ?>">

        <div class="form-group">
            <label>Rental Start Date & Time:</label>
            <input type="datetime-local" class="form-control" name="rental_start" id="rental_start" required onchange="calculatePrice()">
        </div>

        <div class="form-group">
            <label>Rental End Date & Time:</label>
            <input type="datetime-local" class="form-control" name="rental_end" id="rental_end" required onchange="calculatePrice()">
        </div>

        <div class="price-calculation" id="price_calculation">
            Total Price: Rs. 0.0
        </div>

        <button type="submit" class="btn btn-primary mt-3">Confirm Rental</button>
    </form>
</div>

<script>
    const pricePerDay = <?php echo htmlspecialchars($vehicle['price']); ?>;  // Price per day for the selected bicycle

    // Function to calculate rental price
    function calculatePrice() {
        const start = document.getElementById('rental_start').value;
        const end = document.getElementById('rental_end').value;

        if (start && end) {
            const startDate = new Date(start);
            const endDate = new Date(end);
            const timeDifference = endDate - startDate; // Difference in milliseconds
            const dayDifference = timeDifference / (1000 * 3600 * 24); // Convert milliseconds to days

            if (dayDifference > 0) {
                const totalPrice = dayDifference * pricePerDay;
                document.getElementById('price_calculation').textContent = `Total Price: RS. ${totalPrice.toFixed(2)}`;
            } else {
                document.getElementById('price_calculation').textContent = `Total Price: RS. 0.0     (Invalid rental duration)`;
            }
        }
    }
</script>

</body>
</html>
