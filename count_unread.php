<?php
$pdo = new PDO("mysql:host=localhost;dbname=rental_pass","root","");
echo $pdo->query("SELECT COUNT(*) FROM messages WHERE sender='user' AND is_seen=0")->fetchColumn();
