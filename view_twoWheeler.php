<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'database.php';
$user_id = $_SESSION['user_id'];

// Fetch user information for sidebar (optional)
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
    <title>Available Two-Wheelers</title>
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
    transition: background-color 0.3s;
}

.sidebar a:hover {
    background-color: #495057;
}

.content {
    margin-left: 250px;
    padding: 20px;
}

.row > .col-md-4 {
    display: flex;
    align-items: stretch; /* Make all cards equal height */
}

.card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    height: 100%; /* Fill column height */
    max-height: 400px; /* Limit max height */
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    margin-bottom: 30px;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

.card-img-top {
    height: 180px;
    width: 100%;
    object-fit: cover;
    border-radius: 12px 12px 0 0;
    flex-shrink: 0;
}

.card-body {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 15px;
    overflow: hidden;
}

.card-title {
    font-weight: 600;
    margin-bottom: 10px;
    color: #333;
}

.card-text {
    color: #555;
    font-size: 0.9rem;
    margin-bottom: 15px;
}

.btn-primary {
    border-radius: 25px;
    padding: 10px 0;
    font-weight: 600;
    width: 100%;
    background-color: #007bff;
    border: none;
    transition: background-color 0.3s ease;
}

.btn-primary:hover {
    background-color: #0056b3;
    color: #fff;
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
                <a class="nav-link" href="view_twoWheeler.php">View BIkes</a>
                <a class="nav-link" href="view_fourWheeler.php">View Cars</a>
                <a class="nav-link" href="your_rentals.php">Your Rentals</a>
                <a class="nav-link" href="edit_profile.php">Edit Profile</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="content">
            <h1 class="mb-4">Available Bikes</h1>
            <div class="row">
                <?php
                $stmt = $conn->prepare("SELECT TWID, name, model, price, photo FROM two_wheeler WHERE available = 1");
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    while ($bike = $result->fetch_assoc()) {
                        $photoPath = !empty($bike['photo']) ? htmlspecialchars($bike['photo']) : 'default_bike.png';
                        echo "
                        <div class='col-md-4 mb-4'>
                            <div class='card h-100'>
                                <img src='" . $photoPath . "' class='card-img-top' alt='Bike Image'>
                                <div class='card-body d-flex flex-column justify-content-between'>
                                    <div>
                                        <h5 class='card-title'>" . htmlspecialchars($bike['name']) . "</h5>
                                        <p class='card-text'><strong>Model:</strong> " . htmlspecialchars($bike['model']) . "</p>
                                        <p class='card-text'><strong>Price:</strong> Rs. " . htmlspecialchars($bike['price']) . " / day</p>
                                    </div>
                                    <a href='rent_vehicle?id=" . htmlspecialchars($bike['TWID']) . "' class='btn btn-primary mt-3'>Rent Now</a>
                                </div>
                            </div>
                        </div>";
                    }
                } else {
                    echo "<p>No two-wheelers available for rent at the moment.</p>";
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.6/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
