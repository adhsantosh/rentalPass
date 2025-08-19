<?php
session_start();
require 'database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Get bike ID
if (!isset($_GET['id'])) {
    header("Location: manage_twoWheeler.php");
    exit();
}

$bike_id = $_GET['id'];

// Fetch bike info
$stmt = $conn->prepare("SELECT * FROM two_wheeler WHERE TWID = ?");
$stmt->bind_param("i", $bike_id);
$stmt->execute();
$result = $stmt->get_result();
$bike = $result->fetch_assoc();
$stmt->close();

if (!$bike) {
    $_SESSION['error'] = "Bike not found!";
    header("Location: manage_twoWheeler.php");
    exit();
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_bike'])) {
    $name = $_POST['name'];
    $model = $_POST['model'];
    $price = $_POST['price'];
    $photo_path = $bike['photo'];

    // Handle new photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (in_array($extension, $allowed_types)) {
            $target_dir = 'uploads/';
            if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
            $photo_path_new = $target_dir . time() . '_' . basename($_FILES['photo']['name']);
            move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path_new);

            // Delete old photo
            if (file_exists($photo_path)) unlink($photo_path);

            $photo_path = $photo_path_new;
        } else {
            $_SESSION['error'] = "Only JPG, PNG, or GIF files are allowed.";
            header("Location: edit_twoWheeler.php?id=$bike_id");
            exit();
        }
    }

    // Update bike
    $stmt = $conn->prepare("UPDATE two_wheeler SET name = ?, model = ?, price = ?, photo = ? WHERE TWID = ?");
    $stmt->bind_param("ssdsi", $name, $model, $price, $photo_path, $bike_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Bike updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating bike: " . $stmt->error;
    }
    $stmt->close();
    header("Location: manage_twoWheeler.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Two-Wheeler</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<link rel="stylesheet" href="admin-common.css">
<style>
    .form-container {
        background: white; 
        padding: 30px; 
        border-radius: 10px; 
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        max-width: 600px; 
        margin: 40px auto;
    }
    .bike-img-preview {
        width: 100%;
        height: 200px;
        object-fit: contain;
        margin-top: 10px;
        border-radius: 10px;
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
    <div class="form-container">
        <h2 class="text-center mb-4">Edit Bike</h2>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Bike Name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($bike['name']) ?>" required>
            </div>
            <div class="form-group">
                <label>Model</label>
                <input type="text" name="model" class="form-control" value="<?= htmlspecialchars($bike['model']) ?>" required>
            </div>
            <div class="form-group">
                <label>Price (per day/hour)</label>
                <input type="number" name="price" class="form-control" value="<?= htmlspecialchars($bike['price']) ?>" required>
            </div>
            <div class="form-group">
                <label>Photo (optional)</label>
                <input type="file" name="photo" class="form-control-file">

                <?php if (!empty($bike['photo'])): ?>
                    <img src="<?= htmlspecialchars($bike['photo']) ?>" class="bike-img-preview">
                <?php endif; ?>
                
            </div>
            <button type="submit" name="update_bike" class="btn btn-success">Update Bike</button>
            <a href="manage_twoWheeler.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
