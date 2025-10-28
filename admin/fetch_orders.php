<?php
include '../includes/db.php';

$query = "SELECT * FROM orders ORDER BY order_date DESC";
$result = $conn->query($query);

// รวม order_items ทั้งหมด
$order_items_map = [];
$order_items_result = $conn->query("SELECT order_id, product_name, quantity, price FROM order_items");
while ($item = $order_items_result->fetch_assoc()) {
    $order_items_map[$item['order_id']][] = $item;
}

while ($order = $result->fetch_assoc()) {
    $items_json = htmlspecialchars(json_encode($order_items_map[$order['order_id']] ?? []), ENT_QUOTES, 'UTF-8');
    echo "
    <tr>
        <td>{$order['order_id']}</td>
        <td>{$order['total']} บาท</td>
        <td>{$order['payment_method']}</td>
        <td>
            <button class='btn btn-sm btn-warning update-status' 
                data-order-id='{$order['order_id']}'
                data-current-status='{$order['status']}'>
                {$order['status']}
            </button>
        </td>
        <td>
            <button class='btn btn-info btn-sm view-details'
                data-order-id='{$order['order_id']}'
                data-items='{$items_json}'>
                <i class='fas fa-eye'></i> ดูรายละเอียด
            </button>
        </td>
    </tr>";
}
?>