<?php
session_start();
require 'database.php';
$from = $_POST['from'];
$to = $_POST['to'];
$message = $_POST['message'];
$type = $_POST['type'];

$sender_id = ($type === 'admin') ? 0 : $from;
$receiver_id = ($type === 'admin') ? $to : 0;

$stmt = $conn->prepare("INSERT INTO messages (sender, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)");
$stmt->bind_param("siis", $type, $sender_id, $receiver_id, $message);
$stmt->execute();
?>