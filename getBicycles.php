<?php
require 'database.php';

$result = $conn->query("SELECT * FROM vehicles WHERE available = 1");
$bicycles = [];

while ($row = $result->fetch_assoc()) {
    $bicycles[] = $row;
}

echo json_encode($bicycles);
?>
