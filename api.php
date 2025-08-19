<?php
header('Content-Type: application/json');

// Connect to DB (your connection code)
$conn = new mysqli("localhost", "root", "", "rental_pass");

if ($conn->connect_error) {
    die(json_encode(["error" => "DB connection failed"]));
}

$twoWheeler = [];
$fourWheeler = [];

$twResult = $conn->query("SELECT name, model, price, available FROM two_wheeler");
if ($twResult) {
    while ($row = $twResult->fetch_assoc()) {
        $twoWheeler[] = $row;
    }
}

$fwResult = $conn->query("SELECT name, model, price, available FROM four_wheeler");
if ($fwResult) {
    while ($row = $fwResult->fetch_assoc()) {
        $fourWheeler[] = $row;
    }
}

echo json_encode([
    "two_wheeler" => $twoWheeler,
    "four_wheeler" => $fourWheeler
]);

$conn->close();
