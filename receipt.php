<?php  
include 'includes/db.php';  

// ตรวจสอบว่าได้ส่งค่า order_id หรือไม่
if (!isset($_GET['order_id'])) {
    die('Order ID is missing');
}

$order_id = $_GET['order_id'];

function formatThaiDate($dateStr) {
    $months_short = [
        "", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.",
        "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."
    ];
    $timestamp = strtotime($dateStr);
    $day = date("j", $timestamp);
    $month = (int)date("n", $timestamp);
    $year = date("Y", $timestamp) + 543;
    $time = date("H:i", $timestamp);
    return "$day {$months_short[$month]} $year ($time)";
}
// คำสั่ง SQL ดึงข้อมูลจากตาราง orders และ order_items โดยไม่ดึงอีเมล
$query = "SELECT o.*, u.full_name, u.address, oi.product_name, oi.quantity, oi.price, oi.extra
          FROM orders o
          INNER JOIN users u ON o.user_id = u.id  
          INNER JOIN order_items oi ON o.order_id = oi.order_id
          WHERE o.order_id = ?";  

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $items = [];
    $first_row = $result->fetch_assoc();  // บรรทัดแรก

    $order = $first_row; // ใช้สำหรับข้อมูลคำสั่งซื้อ

    $items[] = $first_row; // เพิ่มรายการสินค้าแรกเข้ารายการ

    while ($row = $result->fetch_assoc()) {
        $items[] = $row; // ดึงสินค้ารายการถัด ๆ ไป
    }
} else {
    echo "ไม่พบข้อมูลออเดอร์";
    exit;
}

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ใบเสร็จคำสั่งซื้อ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KyZXEJZf+SzZXkO8d7bK+3mSa+7hPfqzZ47cHHgppPA5q+rpz7HzSZsBhFqljX5F" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            margin: 0;
            padding: 0;
        }
        /* ให้ SweetAlert2 อยู่ด้านบนสุด ไม่เบลอ */
        .swal2-container {
            z-index: 9999 !important;
        }
        .receipt-container {
            max-width: 480px;
            margin: 20px auto;
            background-color: #fff;
            border-radius: 16px;
            padding: 30px 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
            background-color: #fff;
        }

        .receipt-container:before {
            content: '';
            position: absolute;
            top: -30px;
            left: 0;
            width: 100%;
            height: 40px;
            background-color: #fff;
            clip-path: polygon(0 0, 100% 0, 50% 100%);
        }

        .logo img {
            width: 100px;
            margin: 0 auto 10px;
            display: block;
        }

        .receipt-header {
            max-width: 500px;
            margin: 0 auto;
        }

        .receipt-header h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .receipt-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .label {
            color: gray;
        }

        .value {
            text-align: right;
        }

        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        table th, table td {
            padding: 8px;
            text-align: center;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
            font-size: 0.8rem;
        }

        .total {
            text-align: right;
            font-size: 1rem;
            font-weight: bold;
            margin-top: 20px;
            color: green;
        }

        .footer {
            text-align: center;
            font-weight: 500;
            color: #666;
            margin-top: 30px;
        }
.blur-only {
    filter: blur(5px);
    pointer-events: none;
    transition: filter 0.3s ease;
}
    </style>
</head>
<body>

<div class="container">
    <div class="receipt-container">
      <script>
  window.onload = function() {
    const content = document.querySelector('.receipt-container');

    // เพิ่มคลาสเบลอ
    content.classList.add('blur-only');

    // แสดง SweetAlert โหลด
    Swal.fire({
        title: 'กำลังโหลดข้อมูล...',
        text: 'กรุณารอสักครู่',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // เอาเบลอออกเมื่อโหลดเสร็จ
    setTimeout(() => {
        Swal.close();
        content.classList.remove('blur-only');
    }, 1500);
};
</script>
        <div class="logo text-center mb-4">
            <img src="assets/poweredby.png" alt="Powered By">
        </div>

        <div class="receipt-header">
            <h2>ใบเสร็จคำสั่งซื้อ</h2>
            <div class="receipt-row"><span class="label">หมายเลขคำสั่งซื้อ:</span><span class="value"><?= $order_id ?></span></div>
            <div class="receipt-row"><span class="label">ลูกค้า:</span><span class="value"><?= htmlspecialchars($order['full_name'] ?? 'ไม่มีข้อมูล') ?></span></div>
            <div class="receipt-row"><span class="label">รหัสลูกค้า:</span><span class="value"><?= htmlspecialchars($order['user_id'] ?? 'ไม่มีข้อมูล') ?></span></div>
            <div class="receipt-row"><span class="label">วันที่:</span><span class="value"><?= formatThaiDate($order['order_date']) ?></span></div>
            <div class="receipt-row"><span class="label">รหัสบิล:</span><span class="value"><?= ucfirst($order['bill_code'] ?? 'ไม่มีข้อมูล') ?></span></div>
            <div class="receipt-row"><span class="label">วิธีชำระเงิน:</span><span class="value"><?= ucfirst($order['payment_method'] ?? 'ไม่มีข้อมูล') ?></span></div>
            <div class="receipt-row"><span class="label">วิธีรับอาหาร:</span><span class="value"><?= ucfirst($order['pickup_method'] ?? 'ไม่มีข้อมูล') ?></span></div>
        </div>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>สินค้า</th>
                    <th>จำนวน</th>
                    <th>ราคา</th>
                </tr>
            </thead>
            <tbody>
                <?php $subtotal = 0; foreach ($items as $row): 
                    $line_total = $row['quantity'] * $row['price'];
                    $subtotal += $line_total;
                ?>
                    <tr>
                        <td><?= htmlspecialchars($row['product_name']) ?><?= $row['extra'] ? " ({$row['extra']})" : "" ?></td>
                        <td><?= $row['quantity'] ?></td>
                        <td><?= number_format($line_total, 2) ?> ฿</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p class="total">ยอดรวม: <?= number_format($subtotal, 2) ?> ฿</p>
        <?php if ($order['discount_code']): ?>
            <p class="total">ส่วนลด (<?= $order['discount_code'] ?>): <?= number_format($subtotal - $order['total'], 2) ?> ฿</p>
        <?php endif; ?>
        <p class="total">ยอดสุทธิ: <?= number_format($order['total'], 2) ?> ฿</p>

        <div class="footer">
            <p>ขอบคุณที่ใช้บริการ</p>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-8kztH4Z6t0lZCxwkk1XsZnsVrEq4j+G6Epvk7tzDFvVsh6rOVpW52uHDyXtFzCdw" crossorigin="anonymous"></script>
</body>
</html>