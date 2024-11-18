<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

require 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $model = $_POST['model'];
    $size = $_POST['price'];
    $available = isset($_POST['available']) ? 1 : 0;

    // Check if a file was uploaded
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $photo = $_FILES['photo'];
        $targetDir = "uploads/"; // Folder where images will be saved
        $targetFile = $targetDir . basename($photo["name"]);
        $uploadOk = true;

        // Validate file type (accept only JPG, JPEG, PNG)
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        if (!in_array($imageFileType, ['jpg', 'jpeg', 'png'])) {
            $message = "<div class='alert alert-danger'>Only JPG, JPEG, and PNG files are allowed.</div>";
            $uploadOk = false;
        }

        // Attempt to upload if valid
        if ($uploadOk && move_uploaded_file($photo["tmp_name"], $targetFile)) {
            // Insert bicycle details along with the photo path
            $stmt = $conn->prepare("INSERT INTO vehicles (name, model, price, available, photo) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sssds", $name, $model, $size, $available, $targetFile);
                if ($stmt->execute()) {
                    $message = "<div class='alert alert-success'>Bicycle added successfully with photo!</div>";
                } else {
                    $message = "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
                }
                $stmt->close();
            } else {
                $message = "<div class='alert alert-danger'>Error preparing statement: " . $conn->error . "</div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>Sorry, there was an error uploading your file.</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Please upload a photo.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Bicycle</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Add Bicycle</h1>
        <?php if (isset($message)) echo $message; ?>
        <form action="add_bicycle.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Bicycle Name</label>
                <input type="text" class="form-control" name="name" required>
            </div>
            <div class="form-group">
                <label for="model">Model</label>
                <input type="text" class="form-control" name="model" required>
            </div>
            <div class="form-group">
                <label for="price">Price</label>
                <input type="text" class="form-control" name="price" required>
            </div>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="available" checked>
                <label class="form-check-label" for="available">Available for Rent</label>
            </div>
            <div class="form-group">
                <label for="photo">Bicycle Photo</label>
                <input type="file" class="form-control-file" name="photo" required>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Add Bicycle</button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.6/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
