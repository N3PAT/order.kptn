<?php  
session_start();  
include '../includes/db.php';  
  
if (!$conn) {  
    die("Connection failed: " . mysqli_connect_error());  
}  
if (!isset($_SESSION['user_id'])) {  
    header("Location: login.php");  
    exit();  
}  
  
$user_id = $_SESSION['user_id'];  

$query = "SELECT * FROM users WHERE id = ?";  
$stmt = $conn->prepare($query);  
$stmt->bind_param("i", $user_id);  
$stmt->execute();  
$result = $stmt->get_result();  
$user = $result->fetch_assoc();  
  
if ($user['role'] != 'admin') {  
    echo "<script> alert('คุณไม่มีสิทธิ์เข้าถึงหน้านี้'); window.location.href='login.php'; </script>";  
    exit();  
}  
  
$query = "SELECT * FROM orders ORDER BY order_date DESC";  
$result = $conn->query($query);  
  
$order_items_map = [];  
$order_items_query = "SELECT order_id, product_name, quantity, price, extra, note FROM order_items";  
$order_items_result = $conn->query($order_items_query);  
while ($item = $order_items_result->fetch_assoc()) {  
    $order_items_map[$item['order_id']][] = $item;  
}  
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายการออเดอร์</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body { font-family: 'Prompt', sans-serif; background: #f8f9fa; }
        h1 { text-align: center; margin: 20px 0; color: #007bff; }
.table th, .table td {
    font-size: 14px;
    padding: 8px;
    white-space: nowrap;
}

.table-responsive {
    font-size: 14px;
}

@media (max-width: 768px) {
    .table th, .table td {
        font-size: 12px;
        padding: 6px;
    }
}
        .modal-content { font-family: 'Prompt', sans-serif; }
    </style>
</head>
<body>
<div class="container mt-3 mb-5">
        <a href="main.php">กลับหน้าหลัก</a>
    <h1>รายการออเดอร์ทั้งหมด</h1>
    <iframe src="https://free.timeanddate.com/clock/i9vu3pww/n28/tlth39/fs19/ftb/th1" frameborder="0" width="93" height="23"></iframe>

<div class="table-responsive">
    <table class="table table-sm table-bordered table-hover">
        <thead>
            <tr>
                <th>หมายเลขออเดอร์</th>
                <th>ยอดรวม</th>
                <th>วิธีชำระเงิน</th>
                <th>สถานะ</th>
                <th>ชื่อผู้สั่ง</th>
                <th>เบอร์โทร</th>
                <th>ที่อยู่</th>
                <th>วิธีการรับ</th>
                <th>รายละเอียด</th>
            </tr>
        </thead>
        <tbody>
                <?php while ($order = $result->fetch_assoc()) { 
                    $user_info_query = "SELECT full_name, phone_number, address FROM users WHERE id = ?";
                    $user_info_stmt = $conn->prepare($user_info_query);
                    $user_info_stmt->bind_param("i", $order['user_id']);
                    $user_info_stmt->execute();
                    $user_info_result = $user_info_stmt->get_result();
                    $user_info = $user_info_result->fetch_assoc();
                ?>
                <tr>
                    <td><?php echo $order['order_id']; ?></td>
                    <td><?php echo $order['total']; ?> บาท</td>
                    <td><?php echo $order['payment_method']; ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning update-status"  
                                data-order-id="<?php echo $order['order_id']; ?>"   
                                data-current-status="<?php echo $order['status']; ?>">  
                            <?php echo $order['status']; ?>  
                        </button>  
                    </td>
                    <td><?php echo $user_info['full_name']; ?></td>
                    <td><?php echo $user_info['phone_number']; ?></td>
                    <td><?php echo $user_info['address']; ?></td>
                    <td><?php echo $order['pickup_method']; ?></td>
                    <td>
                        <button   
                            class="btn btn-info btn-sm view-details"  
                            data-order-id="<?php echo $order['order_id']; ?>"  
                            data-items='<?php echo json_encode($order_items_map[$order["order_id"]] ?? []); ?>'>  
                            <i class="fas fa-eye"></i> ดูรายละเอียด  
                        </button>  
                    </td>
                </tr>
                <?php } ?>
        </tbody>
    </table>
</div>

            </tbody>
        </table>
    </div>
</div>

<!-- Modal รายละเอียดออเดอร์ -->
<div class="modal fade" id="orderDetailModal" tabindex="-1" role="dialog" aria-labelledby="orderDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">รายละเอียดออเดอร์</h5>
<button   
    class="btn btn-info btn-sm view-details"  
    data-order-id="<?php echo $order['order_id']; ?>"  
    data-items='<?php echo json_encode($order_items_map[$order["order_id"]] ?? []); ?>'>  
    <i class="fas fa-eye"></i> ดูรายละเอียด  
</button>
            </div>
            <div class="modal-body" id="order-details-content"></div>
        </div>
    </div>
</div>

<!-- Modal เปลี่ยนสถานะ -->
<div class="modal fade" id="statusModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">เปลี่ยนสถานะออเดอร์</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="status-order-id">
                <select id="new-status" class="form-control">
                    <option value="pending">รอดำเนินการ</option>
                    <option value="processing">กำลังเตรียมอาหาร</option>
                    <option value="delivering">กำลังจัดส่ง</option>
                    <option value="completed">เสร็จสิ้น</option>
                    <option value="cancelled">ยกเลิก</option>
                </select>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
                <button class="btn btn-primary" id="save-status-btn">บันทึก</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function () {
$('.view-details').click(function () {  
    const items = JSON.parse($(this).attr('data-items'));  
    const orderId = $(this).data('order-id'); // รับค่า order_id

    let html = '<table class="table table-striped"><thead><tr><th>ชื่อเมนู</th><th>จำนวน</th><th>ราคา</th><th>พิเศษ</th><th>หมายเหตุ</th></tr></thead><tbody>';  
    if (items.length === 0) {  
        html += '<tr><td colspan="5" class="text-center">ไม่มีรายการ</td></tr>';  
    } else {  
        items.forEach(item => {  
            let extraText = item.extra ? item.extra : 'ไม่มี'; 
            let noteText = item.note ? item.note : 'ไม่มีหมายเหตุ'; 
            html += `<tr><td>${item.product_name}</td><td>${item.quantity}</td><td>${item.price}</td><td>${extraText}</td><td>${noteText}</td></tr>`;
        });  
    }  
    html += '</tbody></table>';  

    // เพิ่มปุ่มดูบิลด้านล่างตาราง
    html += `<div class="text-right mt-3">
                <a href="receipt.php?order_id=${orderId}" class="btn btn-success" target="_blank">
                    <i class="fas fa-file-invoice"></i> ดูบิล
                </a>
            </div>`;

    $('#order-details-content').html(html);  
    $('#orderDetailModal').modal('show').css('display', 'block');  
});

    $('#save-status-btn').click(function () {  
        const orderId = $('#status-order-id').val();  
        const newStatus = $('#new-status').val();  

        $.post('update_order_status.php', {  
            order_id: orderId,  
            status: newStatus  
        }, function (res) {  
            if (res.success) {  
                Swal.fire("สำเร็จ!", "สถานะอัปเดตแล้ว", "success").then(() => location.reload());  
            } else {  
                Swal.fire("เกิดข้อผิดพลาด", res.message, "error");  
            }  
        }, 'json');  
    });
});
</script>
</body>
</html>