<?php

header('Content-Type: application/json; charset=utf-8');

// เชื่อมต่อฐานข้อมูล

$servername = "sql102.infinityfree.com";

$username = "if0_40241877";

$password = "IHPjYJ0wM7ZO3";

$dbname = "if0_40241877_kptn";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {

    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));

}

// ดึงข้อมูลจากตาราง orders

$sql = "SELECT order_id, user_id, total, payment_method, pickup_method, status, order_date FROM orders ORDER BY order_id DESC";

$result = $conn->query($sql);

$data = [];

while ($row = $result->fetch_assoc()) {

    $data[] = $row;

}

$conn->close();

echo json_encode($data, JSON_UNESCAPED_UNICODE);

?>