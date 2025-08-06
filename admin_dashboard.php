<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
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
            background-color: #f8f9fa; /* Light background color */
        }
        .sidebar {
            position: fixed; /* Fix the sidebar in place */
            top: 0;
            left: 0;
            height: 100vh; /* Full height */
            background-color: #343a40; /* Dark background for sidebar */
            padding: 20px;
            overflow-y: auto; /* Enable scrolling if needed */
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
    <div class="sidebar">
        <h3 class="text-white text-center">Admin Menu</h3>
        <nav class="nav flex-column">
            <a class="nav-link" href="manage_twoWheeler.php">Manage Two-Wheeler</a>
            <a class="nav-link" href="manage_fourWheeler.php">Manage Four-Wheler</a>
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
        <!-- Additional dashboard content can go here -->
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.6/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
