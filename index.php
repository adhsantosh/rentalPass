<?php
// Include database connection
require 'database.php';
session_start();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Query to get the latest bicycles
$stmt = $conn->prepare("SELECT * FROM vehicles WHERE available = 1 ORDER BY VID DESC");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental Pass - Bicycle Rental Platform</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        /* General Styles */
        body {
            background-color: #1a1a2e;
            color: #e8e8f0;
            font-family: Arial, sans-serif;
        }
        header {
            background-color: #162447;
            color: #ffffff;
            padding: 15px 0;
            text-align: center;
            font-weight: bold;
        }
        nav a {
            color: #e8e8f0;
            margin: 0 12px;
            font-size: 1rem;
            text-decoration: none;
            font-weight: 500;
        }
        nav a:hover {
            color: #fddb3a;
            text-decoration: underline;
        }

        /* Banner Section */
        .banner {
            text-align: center;
            padding: 30px;
            background-color: #1f4068;
            color: #e8e8f0;
            border-radius: 10px;
            margin-top: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        .banner h2 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        /* Bike Card Styles */
        .bike-card {
            background: #162447;
            border: none;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            overflow: hidden;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s, box-shadow 0.3s;
            height: 340px;
            color: #e8e8f0;
        }
        .bike-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
        }
        .bike-card img {
            width: 100%;
            height: 140px;
            object-fit: cover;
            border-radius: 8px;
        }
        .bike-card-body {
            padding-top: 10px;
        }
        .bike-card-body h5 {
            font-size: 1.1rem;
            color: #e8e8f0;
            margin-bottom: 6px;
            font-weight: 600;
        }
        .bike-card-body p {
            font-size: 0.85rem;
            color: #a1a1b3;
            margin-bottom: 5px;
        }
        .price {
            font-size: 1.1rem;
            font-weight: bold;
            color: #fddb3a;
        }

        /* Rent Button */
        .btn-rent {
            background-color: #fddb3a;
            color: #162447;
            border-radius: 20px;
            padding: 6px 16px;
            border: none;
            font-size: 0.85rem;
            transition: background-color 0.3s, color 0.3s;
            margin-top: 5px;
            font-weight: bold;
        }
        .btn-rent:hover {
            background-color: #ffc107;
            color: #0f3057;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .banner {
                padding: 20px;
            }
            .bike-card {
                height: auto;
            }
            .bike-card-body h5 {
                font-size: 0.95rem;
            }
            .price, .btn-rent {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>

<header>
    <h1>Rental Pass</h1>
    <nav>
        <a href="index.php">Home</a>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
        <a href="#">Contact</a>
        <a href="#">About</a>
    </nav>
</header>

<div class="container">
    <!-- Banner Section -->
    <section class="banner mt-4">
        <h2>Rent a Bike Today!</h2>
        <p>Find the perfect bicycle for your journey.</p>
    </section>

    <!-- Bicycle List Section -->
    <section id="bicycles" class="mt-5">
        <h3 class="text-center mb-4" style="color: #fddb3a;">Available Bicycles</h3>
        <div class="row">
            <?php
            if ($result->num_rows > 0) {
                while ($bike = $result->fetch_assoc()) {
                    echo "<div class='col-md-3 col-sm-6 mb-4'>
                            <div class='bike-card'>
                                <img src='" . htmlspecialchars($bike['photo']) . "' alt='Bike Image'>
                                <div class='bike-card-body'>
                                    <h5>" . htmlspecialchars($bike['name']) . "</h5>
                                    <p>Model: " . htmlspecialchars($bike['model']) . "</p>
                                    <p class='price'>Rs. " . htmlspecialchars($bike['price']) . "/day</p>
                                    <button class='btn-rent' onclick=\"handleRent(" . $bike['VID'] . ")\">Rent Now</button>
                                </div>
                            </div>
                        </div>";
                }
            } else {
                echo "<p class='text-center' style='color: #fddb3a;'>No bicycles available at the moment.</p>";
            }
            ?>
        </div>
    </section>
</div>

<script>
function handleRent(bikeId) {
    <?php if ($isLoggedIn): ?>
        window.location.href = `rentBicycle.php?id=${bikeId}`;
    <?php else: ?>
        alert("Please log in to rent a bicycle.");
        window.location.href = "login.php";
    <?php endif; ?>
}
</script>

</body>
</html>
