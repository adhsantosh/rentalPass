<?php
require 'database.php'; // Ensure you have the database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Hash the password

    // Validate server-side
    if (!preg_match("/^9[0-9]{9}$/", $phone)) {
        echo "<div class='alert alert-danger'>Phone number must start with 9 and be exactly 10 digits.</div>";
    } else {
        // Prepare and execute the SQL statement
        $stmt = $conn->prepare("INSERT INTO users (name, phone, address, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $phone, $address, $password);

        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>Registration successful!</div>";
            header("Location: index.php");
            exit();
        } else {
            echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration - Rental Pass</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        /* Premium Dark Theme */
        body {
            background-color: #1a1a2e;
            color: #e8e8f0;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 400px;
            margin-top: 50px;
            padding: 20px;
            background-color: #162447;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            color: #e8e8f0;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #fddb3a;
        }
        .form-control {
            border-radius: 20px;
            padding: 12px;
            background-color: #1f4068;
            color: #e8e8f0;
            border: 1px solid #5a5a8b;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.2);
        }
        .form-control:focus {
            border-color: #fddb3a;
            box-shadow: 0 0 5px rgba(253, 219, 58, 0.5);
            background-color: #24344d;
            color: #e8e8f0;
        }
        .btn-primary {
            width: 100%;
            padding: 12px;
            border-radius: 20px;
            background-color: #fddb3a;
            color: #162447;
            font-weight: bold;
            transition: background-color 0.3s, color 0.3s;
        }
        .btn-primary:hover {
            background-color: #ffc107;
            color: #0f3057;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>User Registration</h2>
        <form id="registrationForm" action="register.php" method="POST" onsubmit="return validateForm()">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" class="form-control" name="name" placeholder="Enter your name" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" class="form-control" name="phone" placeholder="Enter your phone number" required>
            </div>
            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" class="form-control" name="address" placeholder="Enter your address" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Register</button>
        </form>
    </div>

    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.6/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        function validateForm() {
            // Validate phone number
            var phone = document.forms["registrationForm"]["phone"].value;
            var phonePattern = /^9[0-9]{9}$/;
            if (!phonePattern.test(phone)) {
                alert("Phone number must start with 9 and be exactly 10 digits.");
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
