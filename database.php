<?php
// Database configuration
$servername = "localhost";  // Change if your database server is not local
$username = "root";         // Replace with your MySQL username
$password = "";             // Replace with your MySQL password
$dbname = "rental_pass";    // Database name

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Setting character set to UTF-8 for proper encoding
$conn->set_charset("utf8");
?>