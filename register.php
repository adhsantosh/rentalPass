<?php
require 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Server-side validation
    if (!preg_match("/^9[0-9]{9}$/", $phone)) {
        echo "<div class='alert alert-danger'>Phone number must start with 9 and be exactly 10 digits.</div>";
    } elseif (strlen($password) < 6) {
        echo "<div class='alert alert-danger'>Password must be at least 6 characters long.</div>";
    } elseif ($password !== $confirmPassword) {
        echo "<div class='alert alert-danger'>Passwords do not match.</div>";
    } else {
        // Check for existing phone number
        $check = $conn->prepare("SELECT * FROM users WHERE phone = ?");
        $check->bind_param("s", $phone);
        $check->execute();
        $checkResult = $check->get_result();

        if ($checkResult->num_rows > 0) {
            echo "<div class='alert alert-danger'>Phone number already registered.</div>";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $conn->prepare("INSERT INTO users (name, phone, address, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $phone, $address, $hashedPassword);

            if ($stmt->execute()) {
                echo "<script>
                        alert('Registration successful!');
                        window.location.href = 'login.php';
                      </script>";
                exit();
            } else {
                echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
            }
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
        body {
            background-color: #f6f3f0;
            color: #e8e8f0;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 400px;
            margin-top: 90px;
            padding: 20px;
            background-color: #162447;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            color: #e8e8f0;
        }
        h2 {
            text-align: center;
            margin-bottom: 10px;
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
<?php require_once 'header.php'; ?>

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
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" class="form-control" name="confirm_password" placeholder="Confirm your password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Register</button>
    </form>
</div>

<script>
    function validateForm() {
        const phone = document.forms["registrationForm"]["phone"].value;
        const password = document.forms["registrationForm"]["password"].value;
        const confirmPassword = document.forms["registrationForm"]["confirm_password"].value;
        const phonePattern = /^9[0-9]{9}$/;

        if (!phonePattern.test(phone)) {
            alert("Phone number must start with 9 and be exactly 10 digits.");
            return false;
        }

        if (password.length < 6) {
            alert("Password must be at least 6 characters long.");
            return false;
        }

        if (password !== confirmPassword) {
            alert("Passwords do not match.");
            return false;
        }

        return true;
    }
</script>
<?php include 'footer.php'; ?>

</body>
</html>
