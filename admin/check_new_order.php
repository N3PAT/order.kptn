<?php
// check_new_order.php
session_start();
include '../includes/db.php';

$last_order_id = isset($_GET['last']) ? (int)$_GET['last'] : 0;

$query = "SELECT order_id FROM orders WHERE order_id > ? ORDER BY order_date DESC LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $last_order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $new_order = $result->fetch_assoc();
    echo json_encode(['new_order' => true, 'new_id' => $new_order['order_id']]);
} else {
    echo json_encode(['new_order' => false]);
}
?>