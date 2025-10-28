<?php
session_start();
include '../includes/db.php';

// ตรวจสอบว่าเป็นแอดมิน
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit();
}

$user_id = $_SESSION['user_id'];
$query = $conn->prepare("SELECT role FROM users WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

if (!$user || $user['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์']);
    exit();
}

// รับค่าจาก AJAX
$order_id = $_POST['order_id'] ?? '';
$status = $_POST['status'] ?? '';

$allowed_statuses = ['pending', 'processing', 'delivering', 'completed', 'cancelled'];

if (!in_array($status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'สถานะไม่ถูกต้อง']);
    exit();
}

// อัปเดตสถานะ
$update = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
$update->bind_param("si", $status, $order_id);

if ($update->execute()) {
    echo json_encode(['success' => true, 'message' => 'อัปเดตสถานะสำเร็จ']);
} else {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด']);
}
?>