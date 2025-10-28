<?php  
session_start();  
include '../includes/db.php';  
  
  
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
  
  
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>หน้าหลัก</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Sarabun&display=swap" rel="stylesheet"/>
  <style>
    body {
      font-family: "Sarabun", sans-serif;
      background-color: #f8f9fa;
    }
    .section-title {
      margin-top: 40px;
      margin-bottom: 20px;
      border-bottom: 2px solid #007bff;
      padding-bottom: 5px;
    }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
      <a class="navbar-brand" href="#">กระเพราเต่าน้อย  24 ช้่วโมง</a>
    </div>
  </nav>

  <div class="container mt-4">
    <h1 class="text-center">หน้าหลักกระเพราเต่าน้อย</h1>

    <!-- หมวดการสอน -->
    <h4 class="section-title">ออเดอร์</h4>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card text-center h-100 shadow-sm">
          <div class="card-body">
            <i class="fas fa-book fa-3x text-primary"></i>
            <h5 class="card-title mt-3">เมนู</h5>
            <p class="card-text">เมนู</p>
            <a href="manage_menu.php" class="btn btn-primary">ไปที่หน้า</a>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card text-center h-100 shadow-sm">
          <div class="card-body">
<i class="fas fa-clock fa-3x text-primary"></i>
            <h5 class="card-title mt-3">เปิด-ปิดร้าน</h5>
            <p class="card-text">ตั้งค่า</p>
            <a href="status.php" class="btn btn-primary">ไปที่หน้า</a>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card text-center h-100 shadow-sm">
          <div class="card-body">
            <i class="fas fa-list-alt fa-3x text-primary"></i>
            <h5 class="card-title mt-3">ออเดอร์</h5>
            <p class="card-text">ดูรายการออเดอร์</p>
            <a href="orders.php" class="btn btn-primary">ไปที่หน้า</a>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card text-center h-100 shadow-sm">
          <div class="card-body">
            <i class="fas fa-list-alt fa-3x text-primary"></i>
            <h5 class="card-title mt-3">เช็คเลขบิล</h5>
            <p class="card-text">เช็คบิล</p>
            <a href="search_bill.php" class="btn btn-primary">ไปที่หน้า</a>
          </div>
        </div>
      </div>
    </div>

    <!-- หมวดการเงิน -->
    <h4 class="section-title">รายงาน</h4>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card text-center h-100 shadow-sm">
          <div class="card-body">
            <i class="fas fa-bullhorn fa-3x text-success"></i>
            <h5 class="card-title mt-3">รายงานการเงิน</h5>
            <p class="card-text">รายงาน</p>
            <a href="report.php" class="btn btn-success">ไปที่หน้า</a>
          </div>
        </div>
      </div>
    </div>

    <!-- ออกจากระบบ -->
    <h4 class="section-title">การใช้งาน</h4>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card text-center h-100 shadow-sm">
          <div class="card-body">
            <i class="fas fa-sign-out-alt fa-3x text-danger"></i>
            <h5 class="card-title mt-3">ออกจากระบบ</h5>
            <p class="card-text">ออกจากระบบ</p>
            <a href="logout.php" class="btn btn-danger">ออกจากระบบ</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>