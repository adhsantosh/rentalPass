<!-- this page is only accessible by admin-->
<?php
session_start();
require 'database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle add/delete
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add new bike
    if (isset($_POST['add_bike'])) {
        $name = $_POST['name'];
        $model = $_POST['model'];
        $price = $_POST['price'];
        $photo_path = null;

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            $extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (in_array($extension, $allowed_types)) {
                $target_dir = 'uploads/';
                if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
                $photo_path = $target_dir . time() . '_' . basename($_FILES['photo']['name']);
                move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path);
            } else {
                $_SESSION['error'] = "Only JPG, PNG, or GIF files are allowed.";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            }
        }

        $stmt = $conn->prepare("INSERT INTO two_wheeler (name, model, price, photo) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssds", $name, $model, $price, $photo_path);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Bike added successfully!";
        } else {
            $_SESSION['error'] = "Error adding bike: " . $stmt->error;
        }
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Delete bike
    if (isset($_POST['delete_bike'])) {
        $vid = $_POST['vid'];

        $stmt = $conn->prepare("SELECT photo FROM two_wheeler WHERE TWID = ?");
        $stmt->bind_param("i", $vid);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if (file_exists($row['photo'])) {
                unlink($row['photo']);
            }
        }
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM two_wheeler WHERE TWID = ?");
        $stmt->bind_param("i", $vid);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Bike deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting bike: " . $stmt->error;
        }
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Fetch bikes
$twoWheeler = $conn->query("SELECT * FROM two_wheeler");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Two-Wheelers</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="admin-common.css">
    <style>

        .form-container {
            background: white; padding: 30px;
            border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .card {
            border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .bike-img {
            width: 100%; height: 200px; object-fit: contain;
            border-top-left-radius: 10px; border-top-right-radius: 10px;
        }
        .alert {
            margin-top: 20px;
    
        }
        
    </style>
</head>
<body>

<div class="sidebar">
    <h3 class="text-center">Admin Menu</h3>
    <nav class="nav flex-column">
        <a class="nav-link active" href="manage_twoWheeler.php">Manage Two-Wheeler</a>
        <a class="nav-link" href="manage_fourWheeler.php">Manage Four-Wheeler</a>
        <a class="nav-link" href="manage_users.php">Manage Users</a>
        <a class="nav-link" href="view_rentals.php">View Rentals</a>
        <a class="nav-link" href="admin_logout.php">Logout</a>
    </nav>
</div>

<div class="content">
    <h1 class="text-center mb-4">Manage Two-Wheelers</h1>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success" role="alert" id="flash-message">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger" role="alert" id="flash-message">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Add Form -->
    <div class="form-container mb-5">
        <h4 class="mb-3">Add a New Bike</h4>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Bike Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="model">Model</label>
                <input type="text" class="form-control" id="model" name="model" required>
            </div>
            <div class="form-group">
                <label for="price">Price (per day/hour)</label>
                <input type="number" step="0.01" class="form-control" id="price" name="price" required>
            </div>
            <div class="form-group">
                <label for="photo">Bike Photo</label>
                <input type="file" class="form-control-file" id="photo" name="photo" accept="image/*" required>
            </div>
            <button type="submit" name="add_bike" class="btn btn-primary">Add Bike</button>
        </form>
    </div>

    <!-- List of Bikes -->
    <h2 class="mt-5">Current Bikes</h2>
    <?php if ($twoWheeler && $twoWheeler->num_rows > 0): ?>
        <div class="row">
            <?php while ($bike = $twoWheeler->fetch_assoc()): ?>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <img src="<?= htmlspecialchars($bike['photo']) ?>" class="bike-img" alt="<?= htmlspecialchars($bike['name']) ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($bike['name']) ?> - <?= htmlspecialchars($bike['model']) ?></h5>
                            <p><strong>Price:</strong> Rs. <?= htmlspecialchars($bike['price']) ?></p>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this bike?');">
                                <input type="hidden" name="vid" value="<?= $bike['TWID'] ?>">
                                <button type="submit" name="delete_bike" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p>No bikes have been added yet.</p>
    <?php endif; ?>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const flash = document.getElementById("flash-message");
    if (flash) {
        setTimeout(() => {
            flash.style.transition = "opacity 0.5s ease-out";
            flash.style.opacity = 0;
            setTimeout(() => flash.remove(), 500);
        }, 3000);
    }
});
</script>

</body>
</html>
