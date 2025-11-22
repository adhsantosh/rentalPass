<?php
$pdo = new PDO("mysql:host=localhost;dbname=rental_pass", "root", "");
$users = $pdo->query("
    SELECT DISTINCT u.UID, u.name,
           (SELECT message FROM messages m2 WHERE m2.sender='user' AND m2.sender_id=u.UID ORDER BY timestamp DESC LIMIT 1) as last_msg
    FROM users u
    JOIN messages m ON u.UID = m.sender_id AND m.sender='user'
    ORDER BY (SELECT MAX(timestamp) FROM messages WHERE sender_id=u.UID OR receiver_id=u.UID) DESC
")->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($users);