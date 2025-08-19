<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'database.php';
$user_id = $_SESSION['user_id'];

// Fetch user info (optional)
$stmt = $conn->prepare("SELECT * FROM users WHERE UID = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Available Two-Wheelers</title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
<style>1
body { 
    background-color: #f8f9fa; 
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

/* Sidebar */
.sidebar {
    height: 100vh;
    width: 250px;
    background-color: #2c3e50;
    color: #fff;
    flex-shrink: 0;
}
.sidebar h3 {
    padding: 20px;
    margin: 0;
    background-color: #233140;
}
.sidebar .nav-link {
    color: #ecf0f1;
    padding: 15px 20px;
    text-decoration: none;
    display: block;
    border-bottom: 1px solid #34495e;
    transition: background-color 0.3s;
}
.sidebar .nav-link:hover {
    background-color: #34495e;
}

/* Main Content */
.content { 
    margin-top: 20px; 
    margin-left: 100px; 
    padding: 20px; 
}

/* Row & Columns for Cards */
.row {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-start; /* Change to center if you want centered cards */
    gap: 30px; /* Space between cards */
}
.col-md-4 {
    flex: 0 0 calc(33.333% - 20px); /* 3 cards per row */
    max-width: calc(33.333% - 20px);
    display: flex;
}

/* Card Styling */
.card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    height: 100%;
    width: 100%;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.card:hover { 
    transform: translateY(-5px); 
    box-shadow: 0 8px 20px rgba(0,0,0,0.15); 
}

/* Card Image */
.card-img-top {
    height: 180px;
    width: 100%;
    object-fit: fill;
    border-radius: 12px 12px 0 0;
}

/* Card Body */
.card-body {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    padding: 15px;
}

/* Text wrapper inside card body */
.card-text-wrapper { flex-grow: 0; }

/* Card Titles and Texts */
.card-title { font-weight: 600; margin-bottom: 10px; color: #333; }
.card-text { color: #555; font-size: 0.9rem; margin-bottom: 5px; }

/* Button */
.btn-primary {
    border-radius: 25px;
    padding: 10px 0;
    font-weight: 600;
    width: 100%;
    background-color: #007bff;
    border: none;
    transition: background-color 0.3s ease;
    margin-top: auto; /* push button to bottom */
}
.btn-primary:hover { 
    background-color: #0056b3; 
    color: #fff; 
}

/* Responsive */
@media (max-width: 992px) {
    .col-md-4 { flex: 0 0 calc(50% - 15px); max-width: calc(50% - 15px); }
}
@media (max-width: 576px) {
    .col-md-4 { flex: 0 0 100%; max-width: 100%; }
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
        <h1 class="mb-4 text-center">Available Bikes</h1>
        <div class="row">
            <?php
            $stmt = $conn->prepare("SELECT TWID, name, model, price, photo FROM two_wheeler WHERE available = 1");
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while ($bike = $result->fetch_assoc()) {
                    $photoPath = !empty($bike['photo']) ? htmlspecialchars($bike['photo']) : 'default_bike.png';
                    ?>
                    <div class="col-md-4 mb-4 d-flex">
                        <div class="card">
                            <img src="<?= $photoPath ?>" class="card-img-top" alt="Bike Image">
                            <div class="card-body d-flex flex-column">
                                <div class="card-text-wrapper">
                                    <h5 class="card-title"><?= htmlspecialchars($bike['name']) ?></h5>
                                    <p class="card-text">Model: <?= htmlspecialchars($bike['model']) ?></p>
                                    <p class="card-text"><strong>Price:</strong> Rs. <?= htmlspecialchars($bike['price']) ?> / day</p>
                                </div>
                                <a href="rent_vehicle.php?twid=<?= $bike['TWID'] ?>" class="btn btn-primary">Rent Now</a>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo "<p>No two-wheelers available for rent at the moment.</p>";
            }
            ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.6/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
