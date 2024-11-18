<?php
session_start();
require 'database.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_bike'])) {
        $name = $_POST['name'];
        $model = $_POST['model'];
        $price = $_POST['price'];

        // Handle the image upload
        $photo = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $photo = 'uploads/' . basename($_FILES['photo']['name']);
            move_uploaded_file($_FILES['photo']['tmp_name'], $photo);
        }

        // Insert data into the database
        $stmt = $conn->prepare("INSERT INTO vehicles (name, model, price, photo) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $model, $price, $photo);
        $stmt->execute();
        $success_message = "Bicycle added successfully!";
    } elseif (isset($_POST['delete_bike'])) {
        $vid = $_POST['vid'];
        $stmt = $conn->prepare("DELETE FROM vehicles WHERE VID = ?");
        $stmt->bind_param("i", $vid);
        $stmt->execute();
        $success_message = "Bicycle deleted successfully!";
    }
}

$bicycles = $conn->query("SELECT * FROM vehicles");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bicycles</title>
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
        .content {
            margin-left: 250px;
            padding: 20px;
        }
        .form-container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .card {
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .btn {
            border-radius: 5px;
        }
        .alert {
            margin-top: 20px;
        }
        .bike-img {
    width: 100%;
    height: 200px; /* Set a fixed height for all images */
    object-fit: cover; /* Ensures the image covers the area without distortion */
    border-radius: 5px;
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
        <h1 class="text-center mb-4">Manage Bicycles</h1>

        <!-- Success Message -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <!-- Add Bicycle Form -->
        <div class="form-container">
            <h4 class="mb-3">Add a New Bicycle</h4>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Bicycle Name</label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="Enter Bicycle Name" required>
                </div>
                <div class="form-group">
                    <label for="model">Model</label>
                    <input type="text" class="form-control" id="model" name="model" placeholder="Enter Model" required>
                </div>
                <div class="form-group">
                    <label for="price">Price</label>
                    <input type="text" class="form-control" id="price" name="price" placeholder="Enter Price" required>
                </div>
                <div class="form-group">
                    <label for="photo">Bicycle Photo</label>
                    <input type="file" class="form-control-file" id="photo" name="photo" accept="image/*">
                </div>
                <button type="submit" name="add_bike" class="btn btn-primary">Add Bicycle</button>
            </form>
        </div>

        <!-- Bicycle List -->
        <h2 class="mt-5">Current Bicycles</h2>
        <?php if ($bicycles->num_rows > 0): ?>
            <div class="row">
                <?php while ($bike = $bicycles->fetch_assoc()): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <img src="<?php echo htmlspecialchars($bike['photo']); ?>" class="bike-img" alt="Bicycle Image">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($bike['name']); ?> - <?php echo htmlspecialchars($bike['model']); ?></h5>
                                <p><strong>Price:</strong> <?php echo htmlspecialchars($bike['price']); ?></p>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="vid" value="<?php echo $bike['VID']; ?>">
                                    <button type="submit" name="delete_bike" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>No bicycles available for management.</p>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.6/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
