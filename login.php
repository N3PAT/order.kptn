<?php
session_start();  // เริ่มต้น session
include 'includes/db.php'; // เชื่อมต่อฐานข้อมูล

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone_number = $_POST['phone_number'];  // รับข้อมูลเบอร์โทรศัพท์จากฟอร์ม

    // ตรวจสอบข้อมูลที่กรอกในฐานข้อมูล
    $query = "SELECT * FROM users WHERE phone_number = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $phone_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // หากพบผู้ใช้ในฐานข้อมูล
        $user = $result->fetch_assoc();  // ดึงข้อมูลผู้ใช้
        $_SESSION['user_id'] = $user['id'];  // เก็บ ID ของผู้ใช้ใน session
        $_SESSION['full_name'] = $user['full_name'];  // เก็บชื่อผู้ใช้ใน session
        $_SESSION['role'] = $user['role'];

        // เข้าสู่ระบบสำเร็จ
        header("Location: menu.php");  // เปลี่ยนไปที่หน้า menu.php
        exit();
    } else {
        echo "<script>alert('เบอร์โทรศัพท์ไม่ถูกต้องหรือไม่พบผู้ใช้ในระบบ');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบ</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <!-- ลิ้งค์ CSS ที่คุณต้องการ -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.2/dist/sweetalert2.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"/>

    <!-- ลิ้งค์ JavaScript -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.7/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #f8f9fa;
        }

        .container {
            max-width: 500px;
            padding-top: 50px;
        }

        .form-group label {
            font-weight: 600;
        }

        .btn-primary {
            background-color: #007bff;
            border: none;
            padding: 10px 20px;
            font-weight: 600;
        }

        .form-control {
            border-radius: 8px;
            padding: 10px;
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
        }
        #version-text {
  position: fixed;
  bottom: 10px;
  left: 50%;
  transform: translateX(-50%);
  color: #888;
  font-size: 0.9rem;
  z-index: 9999;
}
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2>เข้าสู่ระบบ</h2>
        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="phone_number">เบอร์โทรศัพท์:</label>
                <input type="number" class="form-control" id="phone_number" name="phone_number" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">เข้าสู่ระบบ</button>
        </form>
        <a>ยังไม่ได้เป็นสมาชิก</a>        <a href="register.php" >สมัครสมาชิก</a>
    </div>
<p id="version-text">Version 1.98(99)</p>
</body>
</html>