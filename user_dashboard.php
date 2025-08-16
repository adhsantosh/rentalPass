<?php
session_start();
require 'database.php';
require 'recommendation_helper.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Get user name
$userName = 'User';
$stmt = $conn->prepare("SELECT name FROM users WHERE UID=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $userName = $row['name'];
}
$stmt->close();

// Fetch user's rentals
$rentalQuery = "
    SELECT r.rental_id, r.rental_start, r.rental_end, r.status,
           tw.name AS tw_name, tw.model AS tw_model, tw.price AS tw_price,
           fw.name AS fw_name, fw.model AS fw_model, fw.price AS fw_price
    FROM rentals r
    LEFT JOIN two_wheeler tw ON r.TWID = tw.TWID
    LEFT JOIN four_wheeler fw ON r.FWID = fw.FWID
    WHERE r.UID = ?
    ORDER BY r.rental_start DESC
";
$stmt = $conn->prepare($rentalQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$rentalResult = $stmt->get_result();
$userRentals = [];
while ($row = $rentalResult->fetch_assoc()) {
    $userRentals[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f4f7f6; margin:0; color:#333;}
        .dashboard-wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: #2c3e50; color:#fff; flex-shrink:0; }
        .sidebar h3 { padding:20px; margin:0; background:#233140; }
        .sidebar .nav-link { color:#ecf0f1; padding:15px 20px; text-decoration:none; display:block; border-bottom:1px solid #34495e; }
        .sidebar .nav-link:hover { background:#34495e; }
        .main-content { flex-grow:1; padding:20px 40px; }
        .container { max-width:1200px; margin:0 auto; background:#fff; padding:20px 40px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.05);}
        .header { border-bottom:1px solid #eee; padding-bottom:20px; margin-bottom:20px; }
        .header h1 { margin:0; font-size:24px; }
        h2 { font-size:20px; color:#2c3e50; margin-top:40px; }
        table { width:100%; border-collapse:collapse; margin-top:10px; }
        table th, table td { border:1px solid #ddd; padding:8px; text-align:left; }
        table th { background:#f5f5f5; }
        #recommendations-container { display:flex; flex-wrap:wrap; gap:20px; justify-content:flex-start; margin-top:10px;}
        .vehicle-card { border:1px solid #ddd; border-radius:8px; width:250px; padding:15px; text-align:center; box-shadow:0 2px 5px rgba(0,0,0,0.1); transition: transform 0.2s; }
        .vehicle-card:hover { transform: translateY(-5px); }
        .vehicle-card img { max-width:100%; height:150px; object-fit:cover; border-radius:5px; }
        .vehicle-card h3 { margin:10px 0 5px; font-size:18px; }
        .vehicle-card p { margin:0 0 10px; color:#7f8c8d; }
        .rent-btn { display:inline-block; text-decoration:none; color:#fff; background:#3498db; padding:10px 20px; border-radius:5px; font-weight:bold; }
        .loading { text-align:center; font-style:italic; color:#777; }
    </style>
</head>
<body>
<div class="dashboard-wrapper">
    <div class="sidebar">
        <h3>User Menu</h3>
        <nav>
            <a class="nav-link" href="user_dashboard.php">Dashboard</a>
            <a class="nav-link" href="view_twoWheeler.php">View Bikes</a>
            <a class="nav-link" href="view_fourWheeler.php">View Cars</a>
            <a class="nav-link" href="your_rentals.php">Your Rentals</a>
            <a class="nav-link" href="edit_profile.php">Edit Profile</a>
            <a class="nav-link" href="logout.php">Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <div class="container">
            <div class="header">
                <h1>Welcome, <?php echo htmlspecialchars($userName); ?>!</h1>
            </div>

            <!-- Your Rentals -->
            <h2>Your Rentals</h2>
            <table>
                <thead>
                    <tr>
                        <th>Vehicle Name</th>
                        <th>Model</th>
                        <th>Price</th>
                        <th>Rental Start</th>
                        <th>Rental End</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($userRentals as $r): ?>
                    <?php 
                        $name = $r['tw_name'] ?? $r['fw_name'];
                        $model = $r['tw_model'] ?? $r['fw_model'];
                        $price = $r['tw_price'] ?? $r['fw_price'];
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($name); ?></td>
                        <td><?php echo htmlspecialchars($model); ?></td>
                        <td><?php echo htmlspecialchars($price); ?></td>
                        <td><?php echo $r['rental_start'] ?? '-'; ?></td>
                        <td><?php echo $r['rental_end'] ?? '-'; ?></td>
                        <td><?php echo ucfirst($r['status']); ?></td>
                        <td></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Recommendations -->
            <h2>Recommended For You</h2>
            <div id="recommendations-container">
                <p class="loading">Loading recommendations...</p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('recommendations-container');
    fetch('recommendations.php')
        .then(res => res.json())
        .then(data => {
            container.innerHTML = '';
            if(!data) return;

            const types = ['two_wheeler','four_wheeler'];

            types.forEach(type => {
                // Hybrid
                (data[type].hybrid || []).forEach(v => {
                    const card = document.createElement('div');
                    card.className = 'vehicle-card';
                    const photo = v.photo || 'path/to/default.png';
                    card.innerHTML = `
                        <img src="${photo}" alt="${v.brand}">
                        <h3>${v.brand}</h3>
                        <p>${v.type === 'four_wheeler' ? v.brand : v.brand}</p>
                        <p><strong>Price:</strong> Rs. ${v.price}/day</p>
                        <a href="rent_vehicle.php?type=${v.type==='four_wheeler'?'fourWheeler':'twoWheeler'}&id=${v.id}" class="rent-btn">Rent Now</a>
                    `;
                    container.appendChild(card);
                });

                // Pure CF (yellow background)
                (data[type].cf || []).forEach(v => {
                    const card = document.createElement('div');
                    card.className = 'vehicle-card';
                    card.style.backgroundColor = '#f6f3f0'; // light yellow
                    const photo = v.photo || 'path/to/default.png';
                    card.innerHTML = `
                        <img src="${photo}" alt="${v.brand}">
                        <h3>${v.brand}</h3>
                        <p>${v.type === 'four_wheeler' ? v.brand : v.brand}</p>
                        <p><strong>Price:</strong> Rs. ${v.price}/day</p>
                        <a href="rent_vehicle.php?type=${v.type==='four_wheeler'?'fourWheeler':'twoWheeler'}&id=${v.id}" class="rent-btn">Rent Now</a>
                    `;
                    container.appendChild(card);
                });
            });

            if(container.children.length === 0){
                container.innerHTML = '<p>No recommendations yet.</p>';
            }
        })
        .catch(err => {
            console.error(err);
            container.innerHTML = '<p>Error loading recommendations.</p>';
        });
});


</script>
</body>
</html>
