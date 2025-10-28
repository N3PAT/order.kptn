<?php
include '../includes/db.php';
 session_start();

  
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
  

$searchTerm = $_GET['search'] ?? '';
$results = [];

if ($searchTerm !== '') {
    $query = "SELECT o.order_id, o.bill_code, o.order_date, u.full_name, o.total
          FROM orders o
          JOIN users u ON o.user_id = u.id
          WHERE CAST(o.order_id AS CHAR) LIKE ? 
             OR o.bill_code LIKE ? 
             OR u.full_name LIKE ?
          ORDER BY o.order_date DESC";
    $stmt = $conn->prepare($query);
    $likeTerm = "%$searchTerm%";
    $stmt->bind_param("sss", $likeTerm, $likeTerm, $likeTerm);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>ค้นหาบิล</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
      <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body {
      background-color: #f9f9f9;
      font-family: 'Prompt', sans-serif;
    }
    .search-box {
      background: white;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }
    .card-bill {
      border-left: 6px solid #198754;
      border-radius: 12px;
      margin-bottom: 15px;
      transition: all 0.2s ease;
    }
    .card-bill:hover {
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .card-body small {
      color: #777;
    }
  </style>
</head>
<body class="p-3">
<div class="container">
        <a href="main.php">กลับหน้าหลัก</a>
  <h2 class="text-center mb-4">ค้นหาบิลลูกค้า</h2>
  
  <div class="search-box mb-4">
    <form method="GET" class="d-flex flex-column flex-md-row gap-2">
      <input type="text" name="search" class="form-control" placeholder="พิมพ์ชื่อ รหัสบิล หรือหมายเลขคำสั่งซื้อ" value="<?= htmlspecialchars($searchTerm) ?>">
      <button class="btn btn-success" type="submit">ค้นหา</button>
    </form>
  </div>

  <?php if ($searchTerm !== ''): ?>
    <h5 class="mb-3">ผลลัพธ์สำหรับ: <strong><?= htmlspecialchars($searchTerm) ?></strong></h5>

    <?php if (count($results) > 0): ?>
      <?php foreach ($results as $row): ?>
        <div class="card card-bill">
          <div class="card-body">
<h5 class="card-title mb-1">รหัสบิล: <?= htmlspecialchars($row['bill_code'] !== null ? $row['bill_code'] : '') ?></h5>
            <small>หมายเลขคำสั่งซื้อ: <?= $row['order_id'] ?></small><br>
            <small>ลูกค้า: <?= htmlspecialchars($row['full_name']) ?></small><br>
            <small>วันที่: <?= date('d/m/Y H:i', strtotime($row['order_date'])) ?></small><br>
            <small>ยอดรวม: <strong><?= number_format($row['total'], 2) ?> ฿</strong></small>
            <div class="mt-3">
              <a href="receipt.php?order_id=<?= $row['order_id'] ?>" class="btn btn-outline-success btn-sm">ดูใบเสร็จ</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="alert alert-warning">ไม่พบข้อมูลที่ตรงกับคำค้นหา</div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>