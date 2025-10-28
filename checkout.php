<?php
session_start();
include 'includes/db.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
header("Location: login.php");
exit();
}

$user_id = $_SESSION['user_id'];
$query = "SELECT full_name, points FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$username = $user['full_name'];
$user_points = $user['points'];

if (!isset($_SESSION['cart']) || count($_SESSION['cart']) == 0) {
header("Location: menu.php");
exit();
}

$total = 0;
foreach ($_SESSION['cart'] as $item) {
$total += $item['price'] * $item['quantity'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_method'], $_POST['pickup_method'])) {
$payment_method = $_POST['payment_method'];
$pickup_method = $_POST['pickup_method'];

// สร้างเลขบิล    
$bill_code = 'ORDB-' . strtoupper(uniqid());

if ($payment_method === 'points') {
if ($user_points >= $total) {
$conn->begin_transaction();
try {
// หักพอยต์
$stmt = $conn->prepare("UPDATE users SET points = points - ? WHERE id = ?");
$stmt->bind_param("ii", $total, $user_id);
$stmt->execute();

// เพิ่มคำสั่งซื้อในตาราง orders พร้อมเลขบิล    
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total, payment_method, pickup_method, status, bill_code) VALUES (?, ?, ?, ?, 'pending', ?)");    
        $stmt->bind_param("iisss", $user_id, $total, $payment_method, $pickup_method, $bill_code);    
        $stmt->execute();    

        // ดึง order_id ที่สร้างขึ้นจากการแทรกข้อมูลใน orders    
        $order_id = $stmt->insert_id;    

        // เพิ่มรายการใน order_items    
        foreach ($_SESSION['cart'] as $item) {    
            $extra = is_array($item['extra']) ? implode(", ", $item['extra']) : $item['extra'];    
            $note = is_array($item['note']) ? implode(", ", $item['note']) : $item['note'];    
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_name, quantity, price, extra, note) VALUES (?, ?, ?, ?, ?, ?)");    
            $stmt->bind_param("isidss", $order_id, $item['name'], $item['quantity'], $item['price'], $extra, $note);    
            $stmt->execute();    
        }    

        $conn->commit();    
        $_SESSION['cart'] = []; // ล้างตะกร้า    
        header("Location: payments_progess.php");  // Moved header() above echo    
        exit(); // Don't forget to call exit after header()  
    } catch (Exception $e) {    
        $conn->rollback();    
        echo "<script>Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถดำเนินการได้', 'error');</script>";    
    }    
} else {    
    echo "<script>Swal.fire('พอยต์ไม่พอ', 'คุณมีพอยต์ไม่เพียงพอ', 'error');</script>";    
}  

} elseif ($payment_method === 'cash') {    
    // เพิ่มคำสั่งซื้อในตาราง orders พร้อมเลขบิล    
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total, payment_method, pickup_method, status, bill_code) VALUES (?, ?, ?, ?, 'pending', ?)");    
    $stmt->bind_param("iisss", $user_id, $total, $payment_method, $pickup_method, $bill_code);    
    $stmt->execute();    

    // ดึง order_id ที่สร้างขึ้นจากการแทรกข้อมูลใน orders    
    $order_id = $stmt->insert_id;    

    // เพิ่มรายการใน order_items    
    foreach ($_SESSION['cart'] as $item) {    
        $extra = is_array($item['extra']) ? implode(", ", $item['extra']) : $item['extra'];    
        $note = is_array($item['note']) ? implode(", ", $item['note']) : $item['note'];    
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_name, quantity, price, extra, note) VALUES (?, ?, ?, ?, ?, ?)");    
        $stmt->bind_param("isidss", $order_id, $item['name'], $item['quantity'], $item['price'], $extra, $note);    
        $stmt->execute();    
    }    

    $_SESSION['cart'] = []; // ล้างตะกร้า    
    // ใช้ header() ก่อน    
    header("Location: payments_progess.php");    
    exit(); // หยุดการทำงานเพื่อหลีกเลี่ยงการแสดงผลหลัง header    
}

}
?>  <!DOCTYPE html>  <html lang="th">

<head>    
  <meta charset="UTF-8">    
  <title>หน้าชำระเงิน</title>    
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">    
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">    
  <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">    
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">    
  <style>    
    body {    
      font-family: 'Prompt', sans-serif;    
      background-color: #f8f9fa;    
    }  .navbar {    
  background-color: #007bff;    
  padding: 1rem;    
}    .navbar a, .user-info {
color: white;
font-weight: 500;
}

.payment-option {
width: 120px;
height: 120px;
border: 2px solid #ccc;
border-radius: 12px;
background-color: #f0f0f0;
color: #333;
font-size: 14px;
font-weight: 500;
margin: 5px;
display: flex;
flex-direction: column;
justify-content: center;
align-items: center;
transition: 0.3s;
}

.pickup-button {
width: 120px;
height: 120px;
border: 2px solid #ccc;
border-radius: 12px;
background-color: #f0f0f0;
color: #333;
font-size: 14px;
font-weight: 500;
margin: 5px;
display: flex;
flex-direction: column;
justify-content: center;
align-items: center;
transition: 0.3s;
}

.pickup-button i {
font-size: 24px;
margin-bottom: 5px;
}

.payment-option:hover {
background-color: #e2e6ea;
}

.payment-option.selected {
background-color: #007bff;
color: white;
border-color: #007bff;
}

.payment-option input[type="radio"] {
display: none;
}
.pickup-option input[type="radio"] {
display: none;
}

.pickup-button.active {
background-color: #007bff;
color: white;
border-color: #007bff;
}

.cart-summary {
background-color: #ffffff;
border-radius: 8px;
padding: 15px 20px;
margin-bottom: 20px;
box-shadow: 0 0 10px rgba(0,0,0,0.05);
}
.flex-container {
display: flex;
flex-wrap: wrap; /* ให้ขึ้นบรรทัดใหม่ถ้าจอเล็ก /
gap: 10px;
justify-content: center; / หรือใช้ flex-start ถ้าต้องการชิดซ้าย */

}
</style>

</head>    
<body>  <nav class="navbar">    
  <div class="container d-flex justify-content-between">    
    <a href="menu.php" class="navbar-brand font-weight-bold">เมนูอาหาร</a>    
    <div class="user-info">    
      สวัสดี, <?php echo htmlspecialchars($username); ?> | <a href="logout.php" class="text-white">ออกจากระบบ</a>    
    </div>    
  </div>    
</nav>  <div class="container mt-4 mb-5">    
  <h2 class="text-center mb-4"><i class="fas fa-credit-card"></i> หน้าชำระเงิน</h2>    <div class="cart-summary">    
    <h4 class="mb-0">ยอดรวม:    
<strong><?php echo number_format($total, 2); ?></strong> บาท</h4>    
<p>พอยต์ของคุณ: <strong><?php echo number_format($user_points, 2); ?></strong> พอยต์</p>  </div> <form method="POST"> <h5>เลือกวิธีการชำระเงิน:</h5> <div class="flex-container"> <label class="payment-option"> <input type="radio" name="payment_method" value="cash" required> <i class="fas fa-money-bill-wave fa-2x mb-2"></i> เงินสด </label> <label class="payment-option"> <input type="radio" name="payment_method"value="points" required> <i class="fas fa-star fa-2x mb-2"></i> ใช้พอยต์ </label> </div><h5 class="mt-4">เลือกรูปแบบการรับอาหาร:</h5>    
<div class="flex-container">    
  <label class="pickup-button">    
    <input type="radio" name="pickup_method" value="รับที่ร้าน" required>    
    <i class="fas fa-store"></i> รับที่ร้าน    
  </label>    
  <label class="pickup-button">    
    <input type="radio" name="pickup_method" value="เดลิเวอรี่" required>    
    <i class="fas fa-home"></i> เดลิเวอรี่    
  </label>    
</div>  <div class="text-center mt-4">    
  <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-check-circle"></i> ชำระเงิน</button>    
</div>    
</form> </div> <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> </body> </html>​