<?php
session_start();
require 'database.php';

if (!isset($_SESSION['admin_id'])) {
    die("Access denied.");
}

// Admin functionality, e.g., manage users, vehicles
?>