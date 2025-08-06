<?php
// Include database connection
require 'database.php';
session_start();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
// Query to get the latest two-wheelers
$stmt = $conn->prepare("SELECT * FROM two_wheeler WHERE available = 1 ORDER BY TWID DESC");
$stmt->execute();
$result = $stmt->get_result();
?>
 <?php require_once 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Rental Pass - Vehicle Rental Platform</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
  <link rel="stylesheet" href="style.css">

  <style>
/* General Styles */
body {
  background-color:#f6f3f0;
  color: #333333; /* Dark text for readability */
  font-family: Arial, sans-serif;
  margin: 0;
  padding: 0;
}

/* Container padding */
.container {
  padding-top: 30px;
  margin-top:100px;
  padding-bottom: 30px;
}

/* Banner Section */
.banner {
  text-align: center;
  padding: 40px 20px;
  background-color: #162447	; /* white background */
  color: #ffffff;           /* dark text */
  border-radius: 10px;
  margin-top: 20px;
  margin-bottom: 40px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

/* Vehicle options links */
.vehicle-options {
  display: flex;
  justify-content: flex-start;
  align-items: center;
  gap: 20px;
  padding: 10px 0;
}

.vehicle-options a {
  text-decoration: none;
  color: #333333; /* dark text */
  background-color:#fddb3a; /* blue background */
  font-weight: 600;
  padding: 8px 16px;
  border-radius: 6px;
  display: inline-block;
  transition: background-color 0.3s ease;
}

.vehicle-options a:hover {
  background-color: #007bff; /* darker blue on hover */
  color: #333333;
}

/* Section headings */
h3.text-center {
  margin-bottom: 30px;
  font-weight: 700;
  letter-spacing: 1px;
  color: #1e90ff; /* blue heading color */
}

/* Bike Card Styles */
.bike-card {
  background:#162447;
  border: none;
  border-radius: 12px;
  padding: 20px;
  text-align: center;
  overflow: hidden;
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
  transition: transform 0.3s, box-shadow 0.3s;
  height: 340px;
  color: #ffffff;
  margin-bottom: 30px;
}

.bike-card:hover {
  transform: translateY(-7px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
}

.bike-card img {
  width: 100%;
  height: 150px;
  object-fit: fill;
  border-radius: 8px;
}

.bike-card-body {
  padding-top: 15px;
}

.bike-card-body h5 {
  font-size: 1.1rem;
  color: white;
  margin-bottom: 6px;
  font-weight: 600;
}

.bike-card-body p {
  font-size: 0.85rem;
  color:white;
  margin: 6px 0;
}

.price {
  font-size: 1.1rem;
  font-weight: bold;
  color: #1e90ff; /* blue price color */
}

/* Rent Button */
.btn-rent {
  background-color: #1e90ff; /* button bg blue */
  color: #333333;
  border-radius: 20px;
  padding: 6px 16px;
  border: none;
  font-size: 0.85rem;
  transition: background-color 0.3s ease, color 0.3s ease;
  margin-top: 12px;
  font-weight: bold;
  cursor: pointer;
}

.btn-rent:hover {
  background-color: #fddb3a
; /* button hover darker blue */
  color: #222222;
}

/* Responsive */
@media (max-width: 768px) {
  .banner {
    padding: 20px;
  }
  .bike-card {
    height: auto;
    margin-bottom: 25px;
    padding: 15px;
  }
  .bike-card-body h5 {
    font-size: 0.95rem;
  }
  .price,
  .btn-rent {
    font-size: 0.85rem;
  }
}

  
  </style>
</head>
<body>

<div class="container">
  <!-- Banner Section -->
  <section class="banner mt-4">
    <h2>Rent a Vehicle Today!</h2>
    <p>Find the perfect Vehicle for your journey.</p>
    <div class="vehicle-options text-center">
      <p><a href="#" onclick="showSection('two')">Two-Wheelers</a></p>
      <p><a href="#" onclick="showSection('four')">Four-Wheelers</a></p>
    </div>
  </section>

  <!-- Two-Wheeler List Section -->
  <section id="two-wheeler-section" class="mt-5">
    <h3 class="text-center mb-4" style="color: #162447;">Available Two-Wheelers</h3>
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
                      <button class='btn-rent' onclick=\"handleRent(" . $bike['TWID'] . ")\">Rent Now</button>
                    </div>
                  </div>
                </div>";
        }
      } else {
        echo "<p class='text-center' style='color: #162447;'>No two-wheelers available at the moment.</p>";
      }
      ?>
    </div>
  </section>

  <!-- Four-Wheeler List Section -->
  <section id="four-wheeler-section" class="mt-5" style="display: none;">
    <h3 class="text-center mb-4" style="color:#162447;">Available Four-Wheelers</h3>
    <div class="row">
      <?php
      // Fetch four-wheelers separately
      $stmt2 = $conn->prepare("SELECT * FROM four_wheeler WHERE available = 1 ORDER BY FWID DESC");
      $stmt2->execute();
      $result2 = $stmt2->get_result();

      if ($result2->num_rows > 0) {
        while ($vehicle = $result2->fetch_assoc()) {
          echo "<div class='col-md-3 col-sm-6 mb-4'>
                  <div class='bike-card'>
                    <img src='" . htmlspecialchars($vehicle['photo']) . "' alt='Vehicle Image'>
                    <div class='bike-card-body'>
                      <h5>" . htmlspecialchars($vehicle['name']) . "</h5>
                      <p>Model: " . htmlspecialchars($vehicle['model']) . "</p>
                      <p class='price'>Rs. " . htmlspecialchars($vehicle['price']) . "/day</p>
                      <button class='btn-rent' onclick=\"handleRent(" . $vehicle['FWID'] . ")\">Rent Now</button>
                    </div>
                  </div>
                </div>";
        }
      } else {
        echo "<p class='text-center' style='color: #162447;'>No four-wheelers available at the moment.</p>";
      }
      ?>
    </div>
  </section>
</div>

<script>
  function handleRent(vehicleId) {
    <?php if ($isLoggedIn): ?>
      window.location.href = `rent_vehicle.php?id=${vehicleId}`;
    <?php else: ?>
      alert("Please log in to rent a vehicle.");
      window.location.href = "login.php";
    <?php endif; ?>
  }

  function showSection(type) {
    const two = document.getElementById("two-wheeler-section");
    const four = document.getElementById("four-wheeler-section");

    if (type === "two") {
      two.style.display = "block";
      four.style.display = "none";
    } else {
      two.style.display = "none";
      four.style.display = "block";
    }
  }
</script>

</body>
</html>
