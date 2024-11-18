<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Sample code to fetch users from the database would go here
require 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user'])) {
    $uid = $_POST['uid'];
    $stmt = $conn->prepare("DELETE FROM users WHERE UID = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $success_message = "User deleted successfully!";
}

$users = $conn->query("SELECT * FROM users");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
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
            margin-bottom: 20px;
        }
        .table {
            margin-top: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .table th {
            background-color: #343a40;
            color: white;
        }
        .btn {
            border-radius: 5px;
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
            <a class="nav-link" href="manage_bicycle.php">Manage Bicycles</a>
            <a class="nav-link" href="manage_users.php">Manage Users</a>
            <a class="nav-link" href="view_rentals.php">View Rentals</a>
            <a class="nav-link" href="admin_logout.php">Logout</a>
        </nav>
    </div>
    <div class="content">
        <h1 class="text-center mb-4">Manage Users</h1>

        <!-- Success Message -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">User List</h5>
                <p class="card-text">Here you can manage users registered in the system.</p>
                
                <!-- User Table -->
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                <td><?php echo htmlspecialchars($user['address']); ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="uid" value="<?php echo $user['UID']; ?>">
                                        <button type="submit" name="delete_user" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user?');">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.6/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
