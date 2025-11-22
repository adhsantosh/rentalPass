<?php
$pdo = new PDO("mysql:host=localhost;dbname=rental_pass", "root", "");
$uid = (int)($_GET['uid'] ?? 0);
if (!$uid) die("Invalid user");

$stmt = $pdo->prepare("SELECT * FROM messages 
    WHERE (sender='user' AND sender_id=?) OR (sender='admin' AND receiver_id=?)
    ORDER BY timestamp ASC");
$stmt->execute([$uid, $uid]);
$msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($msgs as $m) {
    $class = $m['sender'] === 'user' ? 'user' : 'admin';
    $time = date('h:i A', strtotime($m['timestamp']));
    echo "<div class='msg $class'>" . htmlspecialchars($m['message']) . "<time>$time</time></div>";
}