<?php
session_start();
require 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['phone'];
    $password = $_POST['password'];

    // Prepare the statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM users WHERE phone = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo '
        <div style="
            display:flex;
            justify-content:center;
            align-items:center;
            height:100vh;
            font-family:Arial, sans-serif;
            font-size:20px;
            color:#ff0000;
            text-align:center;
        ">
            No user found! Redirecting to registration page in <span id="countdown">3</span> seconds...
        </div>
    
        <script>
            let seconds = 3;
            const countdown = document.getElementById("countdown");
            const interval = setInterval(() => {
                seconds--;
                countdown.textContent = seconds;
                if(seconds <= 0) {
                    clearInterval(interval);
                    window.location.href = "register.php";
                }
            }, 1000);
        </script>';
        exit();
    }
    
    
    
    $user = $result->fetch_assoc();

    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['UID'];
        header("Location: user_dashboard.php");
        exit();
    } else {
        echo "<div class='invalid'>Invalid username or password.</div>";

        // echo "";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Rental Pass</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        /* Premium Dark Theme */
        body {
            background-color: #f6f3f0;
            color: #e8e8f0;
            font-family: Arial, sans-serif;
        }
        .invalid{
            color:red;
            background-color: yellow;
        }
        .navbar {
            background-color: #162447;
        }
        .navbar-brand, .nav-link {
            color: #e8e8f0 !important;
        }
        .navbar-nav .nav-link.active {
            font-weight: bold;
            color: #fddb3a !important;
        }
        .login-section {
            max-width: 400px;
            margin: 150px auto;
            padding: 20px;
            background: #162447;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            color: #e8e8f0;
        }
        .form-control {
            border-radius: 25px;
            padding: 15px;
            margin-bottom: 15px;
            font-size: 16px;
            background-color: #1f4068;
            color: #e8e8f0;
            border: 1px solid #5a5a8b;
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
            border-radius: 25px;
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

<!-- Navbar -->
<!-- <nav class="navbar navbar-expand-lg navbar-light">
    <a class="navbar-brand" href="index.php">Rental Pass</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link" href="index.php">Home</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="contact.php">Contact</a>
            </li>
            <li class="nav-item active">
                <a class="nav-link" href="login.php">Login</a>
            </li>
        </ul>
    </div>
</nav> -->
<?php
require_once 'header.php'
?>


<!-- Login Section -->
<div class="login-section">
    <h2 class="text-center">Login</h2>
    <form id="loginForm" action="login.php" method="POST" onsubmit="return validateLoginForm()">
        <input type="text" class="form-control" name="phone" placeholder="Phone Number" required>
        <input type="password" class="form-control" name="password" placeholder="Password" required>
        <button type="submit" class="btn btn-primary">Login</button>
    </form>
</div>

<!-- jQuery and Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.6/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
    function validateLoginForm() {
        var phone = document.forms["loginForm"]["phone"].value;
        if (phone.trim() === "") {
            alert("Phone number cannot be empty.");
            return false;
        }

        // var password = document.forms["loginForm"]["password"].value;
        // if (password.length < 6) {
        //     alert("Password must be at least 6 characters long.");
        //     return false;
        // }
        return true;
    }
</script>

</body>
</html>
