<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'database.php';
$user_id = $_SESSION['user_id'];

// Fetch user information for the sidebar (optional)
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
    <title>View Bicycles</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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
        .card {
            border: none;
            border-radius: 10px;
            transition: transform 0.2s;
            height: 350px;
            margin-bottom: 30px; /* Adjusted margin */
        }
        .card:hover {
            transform: scale(1.05);
        }
        .card-img-top {
            height: 200px;
            object-fit: cover;
            border-radius: 10px 10px 0 0;
        }
        .btn-primary {
            background-color: #007bff;
            border: none;
            border-radius: 5px;
        }
        .btn-primary:hover {
            background-color: #0056b3;
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
            <h1 class="mb-4">Available Bicycles</h1>
            <div class="row">
                <?php
                $stmt = $conn->prepare("SELECT VID, model, price, photo FROM vehicles");
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    while ($bicycle = $result->fetch_assoc()) {
                        // Use the photo from the database, or a default image if not available
                        $photoPath = !empty($bicycle['photo']) ? htmlspecialchars($bicycle['photo']) : 'default_bike.png';
                        echo "
                        <div class='col-md-4 mb-4'>
                            <div class='card'>
                                <img src='" . $photoPath . "' class='card-img-top' alt='Bicycle Image'>
                                <div class='card-body'>
                                    <h5 class='card-title'>" . htmlspecialchars($bicycle['model']) . "</h5>
                                    <p class='card-text'><strong>Price:</strong> $" . htmlspecialchars($bicycle['price']) . " per hour</p>
                                    <a href='rentBicycle.php?id=" . htmlspecialchars($bicycle['VID']) . "' class='btn btn-primary'>Rent Now</a>


                                </div>
                            </div>
                        </div>
                        ";
                    }
                } else {
                    echo "<p>No bicycles available for rent.</p>";
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
A