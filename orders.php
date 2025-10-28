<?php  
session_start();  
include 'includes/db.php';  

if (!isset($_SESSION['user_id'])) {  
    header("Location: login.php");  
    exit();  
}  

$user_id = $_SESSION['user_id'];  

// ดึงออเดอร์ของผู้ใช้
$query = "SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC";  
$stmt = $conn->prepare($query);  
$stmt->bind_param("i", $user_id);  
$stmt->execute();  
$result = $stmt->get_result();  

// ดึงรายการออเดอร์ทั้งหมด (ไม่จำกัด user) ที่ยังไม่เสร็จสิ้น สำหรับคิว
$queue_query = "SELECT order_id FROM orders WHERE status != 'completed' ORDER BY order_date ASC";
$queue_result = $conn->query($queue_query);

// ดึงรายการสินค้าและข้อมูลที่เกี่ยวข้องจาก order_items
$order_items_map = [];
$order_items_query = "SELECT order_id, product_name, quantity, price, note, extra FROM order_items";  
$order_items_result = $conn->query($order_items_query);

// จัดเก็บรายการสินค้าในแต่ละออเดอร์
while ($item = $order_items_result->fetch_assoc()) {  
    $order_items_map[$item['order_id']][] = $item;  
}

// สร้าง map ของ order_id => queue number
$queue_position_map = [];
$pos = 1;
while ($row = $queue_result->fetch_assoc()) {
    $queue_position_map[$row['order_id']] = $pos++;
}

function generateQRCodeBase64($text) {
    $url = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($text);

    $arrContextOptions=array(
        "ssl"=>array(
            "verify_peer"=>false,
            "verify_peer_name"=>false,
        ),
    );  

    $image = file_get_contents($url, false, stream_context_create($arrContextOptions));
    return 'data:image/png;base64,' . base64_encode($image);
}
?>  

<!DOCTYPE html>  
<html lang="th">  
<head>  
    <meta charset="UTF-8">  
    <title>ติดตามออเดอร์</title>  
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">  
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">  
    <style>  
        body { font-family: 'Prompt', sans-serif; background-color: #f4f6f9; }  
        .card { margin-bottom: 20px; border-radius: 12px; }  

        .order-tracker {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-bottom: 20px;
        }

        .order-tracker::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 5%;
            right: 5%;
            height: 4px;
            background-color: #dee2e6;
            z-index: 0;
        }

        .step {
            text-align: center;
            position: relative;
            z-index: 1;
            flex: 1;
        }

        .step .circle {
            width: 30px;
            height: 30px;
            background-color: #dee2e6;
            border-radius: 50%;
            margin: 0 auto;
            line-height: 30px;
            color: white;
            font-weight: bold;
            z-index: 2;
            position: relative;
        }

        .step.active .circle {
            background-color: #00bcf1;
        }

        .step .label {
            margin-top: 5px;
            font-size: 12px;
        }
        
        .step.cancelled .circle {
            background-color: #dc3545;
        }
        
        .step.cancelled .label {
            color: #dc3545;
            font-weight: bold;
        }
    </style>  
</head>  
<body>  
  
<div class="container mt-4 mb-5">  
    <h3 class="text-center mb-4">ติดตามสถานะออเดอร์ของคุณ</h3>  
  
    <?php while ($order = $result->fetch_assoc()) {  
        $items = isset($order_items_map[$order['order_id']]) ? $order_items_map[$order['order_id']] : [];  
        $total = $order['total'];  
        $discount = $order['discount'] ?? 0;  
        $net_total = $total - $discount;  

        $status = $order['status'];  
        $queue_number = $queue_position_map[$order['order_id']] ?? '-';

        // สร้าง QR Code แบบ Base64
        $qr_base64 = generateQRCodeBase64("https://orderkptn.rf.gd/KPTN/admin/receipt.php?order_id=" . $order['order_id']);
    ?>  
    <div class="card shadow">  
        <div class="card-header bg-white d-flex justify-content-between align-items-center">  
            <strong>หมายเลขออเดอร์ #<?php echo $order['order_id']; ?></strong>  
            <span class="badge badge-<?php echo $status == 'completed' ? 'success' : ($status == 'cancelled' ? 'danger' : 'warning'); ?>">
              <?php echo ucfirst($status); ?>
            </span>
        </div>  
        <div class="card-body">  

            <!-- แถบสถานะแบบวงกลม -->
            <div class="order-tracker mb-3">
                <?php if ($status == 'cancelled') { ?>
                    <div class="step cancelled">
                        <div class="circle">X</div>
                        <div class="label">ยกเลิกแล้ว</div>
                    </div>
                <?php } else { ?>
                    <div class="step active">
                        <div class="circle"><?php echo $queue_number; ?></div>
                        <div class="label">ลำดับคิว</div>
                    </div>
                    <div class="step <?php echo in_array($status, ['pending', 'paid', 'processing', 'delivering', 'completed']) ? 'active' : ''; ?>">
                        <div class="circle">1</div>
                        <div class="label">สั่งซื้อสำเร็จ</div>
                    </div>
                    <div class="step <?php echo in_array($status, ['paid', 'processing', 'delivering', 'completed']) ? 'active' : ''; ?>">
                        <div class="circle">2</div>
                        <div class="label">กำลังเตรียม</div>
                    </div>
                    <div class="step <?php echo in_array($status, ['delivering', 'completed']) ? 'active' : ''; ?>">
                        <div class="circle">3</div>
                        <div class="label">กำลังส่ง</div>
                    </div>
                    <div class="step <?php echo ($status == 'completed') ? 'active' : ''; ?>">
                        <div class="circle">4</div>
                        <div class="label">สำเร็จ</div>
                    </div>
                <?php } ?>
            </div>

            <!-- รายการสินค้า -->
            <ul class="list-group mb-3">  
                <?php foreach ($items as $item) { ?>  
                <li class="list-group-item">  
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <?php echo htmlspecialchars($item['product_name']); ?> x <?php echo $item['quantity']; ?>
                            
                            <?php if (!empty($item['extra'])) { 
                                $extras = explode(',', $item['extra']); ?>
                                <ul class="mb-0 pl-3 text-muted small">
                                    <?php foreach ($extras as $ex) { ?>
                                        <li>+ <?php echo htmlspecialchars(trim($ex)); ?></li>
                                    <?php } ?>
                                </ul>
                            <?php } ?>

                            <?php if (!empty($item['note'])) { ?>
                                <div class="text-muted small">หมายเหตุ: <?php echo htmlspecialchars($item['note']); ?></div>
                            <?php } ?>
                        </div>
                        <span><?php echo number_format($item['price'], 2); ?> บาท</span>
                    </div>
                </li>  
                <?php } ?>  
            </ul>

            <!-- รายการพิเศษและหมายเหตุ -->
            <?php if (!empty($order['special_requests']) || !empty($order['note'])) { ?>
                <div class="mt-3">
                    <?php if (!empty($order['special_requests'])) { ?>
                        <p><strong>รายการพิเศษ:</strong> <?php echo htmlspecialchars($order['special_requests']); ?></p>
                    <?php } ?>
                    <?php if (!empty($order['note'])) { ?>
                        <p><strong>หมายเหตุ:</strong> <?php echo htmlspecialchars($order['note']); ?></p>
                    <?php } ?>
                </div>
            <?php } ?>

            <p>ยอดรวม: <strong><?php echo number_format($total, 2); ?> บาท</strong></p>  
            <p>ส่วนลด: <strong><?php echo number_format($discount, 2); ?> บาท </strong></p>  
            <p class="mb-0">สุทธิ: <strong class="text-success"><?php echo number_format($net_total, 2); ?> บาท</strong></p>
            <p><small class="text-muted">สั่งเมื่อ: <?php echo date("d/m/Y H:i", strtotime($order['order_date'])); ?></small></p>

            <!-- QR Code แบบ Base64 -->
            <div class="text-center mt-3">
                <img src="<?php echo $qr_base64; ?>" alt="QR Code Order #<?php echo $order['order_id']; ?>" style="width:150px;height:150px;">
                <p class="mt-2 mb-0">กรุณาส่งหน้านี้ได้ในไลน์ <a href="https://line.me/R/ti/p/@yourlineid" target="_blank">คลิกที่นี่</a></p>
            </div>

            <div style="text-align: right;">
              <a href="tel:0624156804">ติดต่อร้านค้า</a> | 
              <a href="receipt.php?order_id=<?php echo $order['order_id']; ?>">ออกบิล</a>
            </div>
        </div>  
    </div>  
    <?php } ?>  
  
</div>  
  
</body>  
</html>