<!-- this page is only accessible by admin-->
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
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message'], $_SESSION['message_type']);
}

// Handle Form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_vehicle'])) {
        $name = $_POST['name'];
        $model = $_POST['model'];
        $price = $_POST['price'];
        $available = 1;
        $photo_path = null;

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $targetDir = 'uploads/';
                if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
                $photo_path = $targetDir . time() . '_' . basename($_FILES['photo']['name']);
                move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path);
            } else {
                $_SESSION['message'] = "Only JPG, PNG, or GIF allowed.";
                $_SESSION['message_type'] = "danger";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            }
        }

        $stmt = $conn->prepare("INSERT INTO four_wheeler (name, model, price, available, photo) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdis", $name, $model, $price, $available, $photo_path);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Vehicle added successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error: " . $stmt->error;
            $_SESSION['message_type'] = "danger";
        }
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    if (isset($_POST['delete_vehicle'])) {
        $vid = $_POST['vid'];

        $stmt = $conn->prepare("SELECT photo FROM four_wheeler WHERE FWID = ?");
        $stmt->bind_param("i", $vid);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($v = $res->fetch_assoc()) {
            if (file_exists($v['photo'])) unlink($v['photo']);
        }
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM four_wheeler WHERE FWID = ?");
        $stmt->bind_param("i", $vid);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Vehicle deleted successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Delete failed: " . $stmt->error;
            $_SESSION['message_type'] = "danger";
        }
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Fetch
$vehicles = $conn->query("SELECT * FROM four_wheeler ORDER BY FWID DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Four-Wheelers</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        .vehicle-img {
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
        <a class="nav-link" href="manage_twoWheeler.php">Manage Two-Wheeler</a>
        <a class="nav-link active" href="manage_fourWheeler.php">Manage Four-Wheeler</a>
        <a class="nav-link" href="manage_users.php">Manage Users</a>
        <a class="nav-link" href="view_rentals.php">View Rentals</a>
        <a class="nav-link" href="admin_logout.php">Logout</a>
    </nav>
</div>

<div class="content">
    <h1 class="text-center mb-4">Manage Four-Wheelers</h1>

    <!-- Success/Error Flash Messages -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Add Form -->
    <div class="form-container mb-5">
        <h4 class="mb-3">Add a New Vehicle</h4>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Vehicle Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="model">Model</label>
                <input type="text" class="form-control" id="model" name="model" required>
            </div>
            <div class="form-group">
                <label for="price">Price (per day)</label>
                <input type="number" step="0.01" class="form-control" id="price" name="price" required>
            </div>
            <div class="form-group">
                <label for="photo">Vehicle Photo</label>
                <input type="file" class="form-control-file" id="photo" name="photo" accept="image/*" required>
            </div>
            <button type="submit" name="add_vehicle" class="btn btn-primary">Add Vehicle</button>
        </form>
    </div>

    <!-- Vehicle List -->
    <h2 class="mt-5">Current Vehicles</h2>
    <div class="row">
        <?php while ($v = $vehicles->fetch_assoc()): ?>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <img src="<?= htmlspecialchars($v['photo']) ?>" class="vehicle-img" alt="<?= htmlspecialchars($v['name']) ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($v['name']) ?> - <?= htmlspecialchars($v['model']) ?></h5>
                        <p><strong>Price:</strong> Rs. <?= htmlspecialchars($v['price']) ?></p>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this vehicle?');">
                            <input type="hidden" name="vid" value="<?= $v['FWID'] ?>">
                            <button type="submit" name="delete_vehicle" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto-hide alerts
    setTimeout(() => {
        $('.alert').fadeOut('slow');
    }, 3000);
</script>

</body>
</html>
