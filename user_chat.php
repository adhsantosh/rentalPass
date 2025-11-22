<?php
session_start();
$pdo = new PDO("mysql:host=localhost;dbname=rental_pass", "root", "");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = trim($_POST['message'] ?? '');
    if ($msg && isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender, sender_id, receiver_id, message) VALUES ('user', ?, 0, ?)");
        $stmt->execute([$_SESSION['user_id'], $msg]);
    }
    exit;
}

// Fetch messages
$uid = $_SESSION['user_id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM messages 
    WHERE (sender='user' AND sender_id=?) OR (sender='admin' AND receiver_id=?) 
    ORDER BY timestamp ASC");
$stmt->execute([$uid, $uid]);
$messages = $stmt->fetchAll();

foreach ($messages as $m) {
    $class = $m['sender'] === 'user' ? 'user' : 'admin';
    $time = date('g:i A', strtotime($m['timestamp']));
    echo "<p class='$class'>" . htmlspecialchars($m['message']) . "<span class='time'>$time</span></p>";
}