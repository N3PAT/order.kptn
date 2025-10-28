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
  
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_open = $_POST['is_open'];
    $reopen_datetime = $_POST['reopen_datetime'];

    // แปลงรูปแบบ datetime
    $reopen_datetime = str_replace("T", " ", $reopen_datetime) . ":00";

    // SQL สำหรับเพิ่มข้อมูลใหม่
    $stmt = $conn->prepare("INSERT INTO store_status (is_open, reopen_datetime) VALUES (?, ?)");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("is", $is_open, $reopen_datetime);

    if ($stmt->execute()) {
        echo "<div class='alert success'>เพิ่มสถานะร้านเรียบร้อยแล้ว!</div>";
    } else {
        echo "<div class='alert error'>เกิดข้อผิดพลาด: " . $stmt->error . "</div>";
    }

    $stmt->close();
}

   
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ตั้งค่าสถานะร้าน</title>
      <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background: #f2f6fc;
            padding: 20px;
        }
        form {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            max-width: 400px;
            margin: auto;
        }
        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }
        select, input, button {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        button {
            background-color: #2d6cdf;
            color: white;
            margin-top: 20px;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover {
            background-color: #1a53b0;
        }
        .alert {
            margin: 20px auto;
            max-width: 400px;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>

<form method="post">
          <a href="main.php">กลับหน้าหลัก</a>
    <h2>ตั้งค่าสถานะร้าน</h2>

    <label for="is_open">สถานะร้าน:</label>
    <select name="is_open" id="is_open">
        <option value="1">เปิด</option>
        <option value="0">ปิด</option>
    </select>

    <label for="reopen_datetime">วันที่จะเปิดอีกครั้ง:</label>
    <input type="datetime-local" name="reopen_datetime" id="reopen_datetime" required>

    <button type="submit">บันทึก</button>
</form>

</body>
</html>