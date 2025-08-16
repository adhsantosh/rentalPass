<?php
session_start();
require 'database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Message
$message = '';
$message_type = '';

// Get vehicle ID from query string
if (!isset($_GET['fwid'])) {
    $_SESSION['message'] = "Invalid vehicle ID.";
    $_SESSION['message_type'] = "danger";
    header("Location: manage_fourWheeler.php");
    exit();
}

$fwid = intval($_GET['fwid']);

// Fetch vehicle data
$stmt = $conn->prepare("SELECT * FROM four_wheeler WHERE FWID = ?");
$stmt->bind_param("i", $fwid);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows == 0) {
    $_SESSION['message'] = "Vehicle not found.";
    $_SESSION['message_type'] = "danger";
    header("Location: manage_fourWheeler.php");
    exit();
}
$vehicle = $res->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $model = $_POST['model'];
    $price = $_POST['price'];
    $photo_path = $vehicle['photo']; // Keep old photo if not replaced

    // Handle new photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $targetDir = 'uploads/';
            if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
            $new_photo_path = $targetDir . time() . '_' . basename($_FILES['photo']['name']);
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $new_photo_path)) {
                if (file_exists($photo_path)) unlink($photo_path);
                $photo_path = $new_photo_path;
            }
        } else {
            $message = "Only JPG, PNG, or GIF allowed.";
            $message_type = "danger";
        }
    }

    // Update vehicle in DB
    if (empty($message)) {
        $stmt = $conn->prepare("UPDATE four_wheeler SET name=?, model=?, price=?, photo=? WHERE FWID=?");
        $stmt->bind_param("ssdsi", $name, $model, $price, $photo_path, $fwid);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Vehicle updated successfully!";
            $_SESSION['message_type'] = "success";
            header("Location: manage_fourWheeler.php");
            exit();
        } else {
            $message = "Update failed: " . $stmt->error;
            $message_type = "danger";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Vehicle</title>
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
    .vehicle-img {
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
        <h3 class="mb-4 text-center">Edit Vehicle</h3>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Vehicle Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($vehicle['name']) ?>" required>
            </div>
            <div class="form-group">
                <label for="model">Model</label>
                <input type="text" class="form-control" id="model" name="model" value="<?= htmlspecialchars($vehicle['model']) ?>" required>
            </div>
            <div class="form-group">
                <label for="price">Price (per day)</label>
                <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?= htmlspecialchars($vehicle['price']) ?>" required>
            </div>
            <div class="form-group">
                <label for="photo">Change Photo (optional)</label>
                <input type="file" class="form-control-file" id="photo" name="photo" accept="image/*">
            </div>

            <button type="submit" class="btn btn-success">Update Vehicle</button>
            <a href="manage_fourWheeler.php" class="btn btn-secondary">Cancel</a>

            <!-- Display current image below form fields -->
            <?php if (!empty($vehicle['photo']) && file_exists($vehicle['photo'])): ?>
                <div class="form-group mt-3">
                    <label>Current Photo</label>
                    <img src="<?= htmlspecialchars($vehicle['photo']) ?>" alt="Current Photo" class="vehicle-img">
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
