<?php
$servername = "sql102.infinityfree.com";
$username = "if0_40241877";
$password = "IHPjYJ0wM7ZO3";
$database = "if0_40241877_kptn";

$conn = new mysqli($servername, $username, $password, $database);
$conn->set_charset("utf8");
mysqli_query($conn, "SET time_zone = '+07:00'");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>