<?php
session_start();
include 'includes/db.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ดึงข้อมูลผู้ใช้จาก session
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$username = $user['full_name'];

// ตรวจสอบว่ามีสินค้าในตะกร้าหรือไม่
if (!isset($_SESSION['cart']) || count($_SESSION['cart']) == 0) {
    echo "<script> alert('ตะกร้าของคุณว่างเปล่า'); window.location.href='menu.php'; </script>";
    exit();
}

// คำนวณยอดรวม
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] * $item['quantity'];
}

// ตรวจสอบรหัสส่วนลด
$discount_amount = 0;
$discount_code = null;

if (!empty($_POST['discount_code'])) {
    $entered_code = trim($_POST['discount_code']);
    $stmt = $conn->prepare("SELECT * FROM discount_codes WHERE code = ? AND expired_at > NOW()");
    $stmt->bind_param("s", $entered_code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($code = $result->fetch_assoc()) {
        if ($code['is_member_only'] && !$user_id) {
            echo "<script>alert('โค้ดนี้ใช้ได้เฉพาะสมาชิกเท่านั้น'); window.location.href='checkout.php';</script>";
            exit();
        } else {
            $discount_amount = $code['discount_amount'];
            $discount_code = $entered_code;
            $total = max(0, $total - $discount_amount);
        }
    } else {
        echo "<script>alert('รหัสส่วนลดไม่ถูกต้องหรือหมดอายุแล้ว'); window.location.href='checkout.php';</script>";
        exit();
    }
}

// ตรวจสอบวิธีการชำระเงินและรูปแบบรับอาหาร
if (isset($_POST['payment_method']) && isset($_POST['pickup_method'])) {
    $payment_method = $_POST['payment_method'];
    $pickup_method = $_POST['pickup_method']; // pickup หรือ delivery
    $note = isset($_POST['note']) ? $_POST['note'] : null;

    $conn->begin_transaction();

    try {
        if ($payment_method == 'points') {
            if ($user['points'] < $total) {
                echo "<script> alert('คุณไม่มีพอยต์เพียงพอในการชำระเงิน'); window.location.href='checkout.php'; </script>";
                exit();
            }

            $new_points = $user['points'] - $total;
            $query = "UPDATE users SET points = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $new_points, $user_id);
            $stmt->execute();
        } elseif ($payment_method != 'cash') {
            echo "<script> alert('กรุณาเลือกวิธีการชำระเงินที่ถูกต้อง'); window.location.href='checkout.php'; </script>";
            exit();
        }

        // บันทึกคำสั่งซื้อ
        $query = "INSERT INTO orders (user_id, total, payment_method, pickup_method, discount_code, status, note) VALUES (?, ?, ?, ?, ?, 'pending', ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iissss", $user_id, $total, $payment_method, $pickup_method, $discount_code, $note);
        $stmt->execute();
        $order_id = $stmt->insert_id;

        // บันทึกรายการสินค้า
        foreach ($_SESSION['cart'] as $item) {
            $extra = isset($item['extra']) ? $item['extra'] : null;
            $item_note = isset($item['note']) ? $item['note'] : null;

            $query = "INSERT INTO order_items (order_id, product_name, quantity, price, extra, note) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isidss", $order_id, $item['name'], $item['quantity'], $item['price'], $extra, $item_note);
            $stmt->execute();
        }

        unset($_SESSION['cart']);
        $conn->commit();

        header("Location: receipt.php?order_id=" . $order_id);
exit();
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script> alert('เกิดข้อผิดพลาดในการชำระเงิน'); window.location.href='checkout.php'; </script>";
        exit();
    }
} else {
    echo "<script> alert('กรุณาเลือกวิธีการชำระเงินและรูปแบบรับอาหาร'); window.location.href='checkout.php'; </script>";
    exit();
}
?>