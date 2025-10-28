<?php
session_start();

// ลบข้อมูลใน session ทั้งหมด
session_unset();

// ทำลาย session
session_destroy();

// ส่งผู้ใช้กลับไปยังหน้า login
header("Location: login.php");
exit();
?>