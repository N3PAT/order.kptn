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
  
// กำหนดค่าเริ่มต้น
$date_from = isset($_POST['date_from']) ? $_POST['date_from'] : '';
$date_to = isset($_POST['date_to']) ? $_POST['date_to'] : '';
$sales_total = 0;
$sales_last_year_total = 0;
$monthly_sales = [];
$weekly_sales = [];
$daily_sales = [];

// ดึงข้อมูลการขายจากฐานข้อมูล
$query = "SELECT * FROM orders WHERE status = 'paid'";
if ($date_from && $date_to) {
    $query .= " AND DATE(order_date) BETWEEN ? AND ?";
}
$stmt = $conn->prepare($query);
if ($date_from && $date_to) {
    $stmt->bind_param("ss", $date_from, $date_to);
}
$stmt->execute();
$result = $stmt->get_result();

// คำนวณยอดขายรวม
while ($row = $result->fetch_assoc()) {
    $sales_total += $row['total'];
    
    // คำนวณยอดขายแยกตามเดือน
    $order_month = date('Y-m', strtotime($row['order_date']));
    $monthly_sales[$order_month][] = $row['total'];

    // คำนวณยอดขายแยกตามสัปดาห์
    $order_week = date('Y-W', strtotime($row['order_date']));
    $weekly_sales[$order_week][] = $row['total'];

    // คำนวณยอดขายแยกตามวัน
    $order_day = date('Y-m-d', strtotime($row['order_date']));
    $daily_sales[$order_day][] = $row['total'];
}

// คำนวณยอดขายปีที่แล้ว
$last_year = date('Y', strtotime('-1 year'));
$query_last_year = "SELECT * FROM orders WHERE status = 'paid' AND YEAR(order_date) = ?";
$stmt_last_year = $conn->prepare($query_last_year);
$stmt_last_year->bind_param("s", $last_year);
$stmt_last_year->execute();
$result_last_year = $stmt_last_year->get_result();
while ($row_last_year = $result_last_year->fetch_assoc()) {
    $sales_last_year_total += $row_last_year['total'];
}

// คำนวณเปอร์เซ็นต์การเปลี่ยนแปลง
$percentage_change = 0;
if ($sales_last_year_total > 0) {
    $percentage_change = (($sales_total - $sales_last_year_total) / $sales_last_year_total) * 100;
}

// คำนวณเปอร์เซ็นต์การเปลี่ยนแปลงแยกตามเดือน
foreach ($monthly_sales as $month => $sales) {
    $last_year_sales = 0;
    foreach ($sales as $sale) {
        $last_year_sales += $sale;  // คำนวณยอดขายปีนี้
    }
    $monthly_sales_total = array_sum($sales);
    if (isset($last_year_sales)) {
        $monthly_percentage_change[$month] = ($monthly_sales_total - $last_year_sales) / $last_year_sales * 100;
    }
}

// คำนวณเปอร์เซ็นต์การเปลี่ยนแปลงแยกตามสัปดาห์
foreach ($weekly_sales as $week => $sales) {
    $last_year_sales = 0;
    foreach ($sales as $sale) {
        $last_year_sales += $sale;
    }
    $weekly_sales_total = array_sum($sales);
    if (isset($last_year_sales)) {
        $weekly_percentage_change[$week] = ($weekly_sales_total - $last_year_sales) / $last_year_sales * 100;
    }
}

// คำนวณเปอร์เซ็นต์การเปลี่ยนแปลงแยกตามวัน
foreach ($daily_sales as $day => $sales) {
    $last_year_sales = 0;
    foreach ($sales as $sale) {
        $last_year_sales += $sale;
    }
    $daily_sales_total = array_sum($sales);
    if (isset($last_year_sales)) {
        $daily_percentage_change[$day] = ($daily_sales_total - $last_year_sales) / $last_year_sales * 100;
    }
}

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายงานยอดการขาย</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 900px;
            margin: 30px auto;
            padding: 30px;
            background-color: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        .sales-total {
            text-align: center;
            font-size: 24px;
            margin-top: 20px;
        }

        .btn-export {
            width: 100%;
            background-color: #007bff;
            color: #fff;
            font-size: 18px;
            padding: 12px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }

        .btn-export:hover {
            background-color: #0056b3;
        }

        .form-inline {
            justify-content: center;
            margin-bottom: 20px;
        }

        .form-inline input[type="date"] {
            margin-right: 10px;
        }

        .form-inline input[type="submit"] {
            background-color: #28a745;
            color: white;
            border-radius: 5px;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
        }

        .form-inline input[type="submit"]:hover {
            background-color: #218838;
        }

        .comparison {
            text-align: center;
            font-size: 20px;
            margin-top: 30px;
        }

        .comparison span {
            font-weight: bold;
        }

        .chart-container {
            margin-top: 40px;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>รายงานยอดการขาย</h1>

    <form method="POST" action="report.php" class="form-inline">
        <label for="date_from">From:</label>
        <input type="date" id="date_from" name="date_from" required class="form-control">
        
        <label for="date_to">To:</label>
        <input type="date" id="date_to" name="date_to" required class="form-control">
        
        <input type="submit" value="แสดงรายงาน" class="btn btn-primary">
    </form>

    <!-- ยอดรวมการขาย -->
    <div class="sales-total">
        <h2>ยอดรวมการขาย: <?php echo number_format($sales_total, 2); ?> บาท</h2>
    </div>

    <!-- เปรียบเทียบการเปลี่ยนแปลงยอดขาย -->
    <div class="comparison">
        <h3>เปรียบเทียบยอดขายปี <?php echo date('Y'); ?> กับ ปีที่ผ่านมา (<?php echo $last_year; ?>)</h3>
        <p>ยอดขายปีที่ผ่านมา: <?php echo number_format($sales_last_year_total, 2); ?> บาท</p>
        <p>เปอร์เซ็นต์การเปลี่ยนแปลง: 
            <span style="color: <?php echo ($percentage_change > 0) ? 'green' : 'red'; ?>">
                <?php echo number_format($percentage_change, 2); ?>%
            </span>
        </p>
    </div>

    <!-- กราฟการขายแยกตามเดือน -->
    <div class="chart-container">
        <h3>กราฟยอดขายแยกตามเดือน</h3>
                <p>เปอร์เซ็นต์การเปลี่ยนแปลง: 
            <span style="color: <?php echo ($percentage_change > 0) ? 'green' : 'red'; ?>">
                <?php echo number_format($percentage_change, 2); ?>%
            </span>
        </p>
        <canvas id="monthlyChart"></canvas>
    </div>

    <!-- กราฟการขายแยกตามสัปดาห์ -->
    <div class="chart-container">
        <h3>กราฟยอดขายแยกตามสัปดาห์</h3>
                <p>เปอร์เซ็นต์การเปลี่ยนแปลง: 
            <span style="color: <?php echo ($percentage_change > 0) ? 'green' : 'red'; ?>">
                <?php echo number_format($percentage_change, 2); ?>%
            </span>
        </p>
        <canvas id="weeklyChart"></canvas>
    </div>

    <!-- กราฟการขายแยกตามวัน -->
    <div class="chart-container">
        <h3>กราฟยอดขายแยกตามวัน</h3>
                <p>เปอร์เซ็นต์การเปลี่ยนแปลง: 
            <span style="color: <?php echo ($percentage_change > 0) ? 'green' : 'red'; ?>">
                <?php echo number_format($percentage_change, 2); ?>%
            </span>
        </p>
        <canvas id="dailyChart"></canvas>
    </div>


</div>
<script>
Swal.fire({
    title: 'ขออภัย',
    text: 'ฟีเจอร์นี้ยังไม่พร้อมให้บริการ',
    icon: 'warning',
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: true,
    confirmButtonText: 'กลับ'
}).then((result) => {
    if (result.isConfirmed) {
        window.location.href = 'main.php';
    }
});
</script>
<script>
    // กราฟยอดขายแยกตามเดือน
    var ctxMonthly = document.getElementById('monthlyChart').getContext('2d');
    var monthlyChart = new Chart(ctxMonthly, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_keys($monthly_sales)); ?>,
            datasets: [{
                label: 'ยอดขาย (บาท)',
                data: <?php echo json_encode(array_map(function($sales) {
                    return array_sum($sales);
                }, $monthly_sales)); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // กราฟยอดขายแยกตามสัปดาห์
    var ctxWeekly = document.getElementById('weeklyChart').getContext('2d');
    var weeklyChart = new Chart(ctxWeekly, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_keys($weekly_sales)); ?>,
            datasets: [{
                label: 'ยอดขาย (บาท)',
                data: <?php echo json_encode(array_map(function($sales) {
                    return array_sum($sales);
                }, $weekly_sales)); ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // กราฟยอดขายแยกตามวัน
    var ctxDaily = document.getElementById('dailyChart').getContext('2d');
    var dailyChart = new Chart(ctxDaily, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_keys($daily_sales)); ?>,
            datasets: [{
                label: 'ยอดขาย (บาท)',
                data: <?php echo json_encode(array_map(function($sales) {
                    return array_sum($sales);
                }, $daily_sales)); ?>,
                backgroundColor: 'rgba(255, 159, 64, 0.2)',
                borderColor: 'rgba(255, 159, 64, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>
<script>
    // กราฟยอดขายแยกตามเดือน
    var monthlyData = <?php echo json_encode(array_values($monthly_sales)); ?>;
    var monthlyLabels = <?php echo json_encode(array_keys($monthly_sales)); ?>;
    var monthlyChartCtx = document.getElementById('monthlyChart').getContext('2d');
    var monthlyChart = new Chart(monthlyChartCtx, {
        type: 'line',
        data: {
            labels: monthlyLabels,
            datasets: [{
                label: 'ยอดขายตามเดือน',
                data: monthlyData.map(function (sales) { return sales.reduce((a, b) => a + b, 0); }),
                borderColor: 'rgba(75, 192, 192, 1)',
                fill: false
            }]
        },
    });

    // กราฟยอดขายแยกตามสัปดาห์
    var weeklyData = <?php echo json_encode(array_values($weekly_sales)); ?>;
    var weeklyLabels = <?php echo json_encode(array_keys($weekly_sales)); ?>;
    var weeklyChartCtx = document.getElementById('weeklyChart').getContext('2d');
    var weeklyChart = new Chart(weeklyChartCtx, {
        type: 'line',
        data: {
            labels: weeklyLabels,
            datasets: [{
                label: 'ยอดขายตามสัปดาห์',
                data: weeklyData.map(function (sales) { return sales.reduce((a, b) => a + b, 0); }),
                borderColor: 'rgba(153, 102, 255, 1)',
                fill: false
            }]
        },
    });

    // กราฟยอดขายแยกตามวัน
    var dailyData = <?php echo json_encode(array_values($daily_sales)); ?>;
    var dailyLabels = <?php echo json_encode(array_keys($daily_sales)); ?>;
    var dailyChartCtx = document.getElementById('dailyChart').getContext('2d');
    var dailyChart = new Chart(dailyChartCtx, {
        type: 'line',
        data: {
            labels: dailyLabels,
            datasets: [{
                label: 'ยอดขายตามวัน',
                data: dailyData.map(function (sales) { return sales.reduce((a, b) => a + b, 0); }),
                borderColor: 'rgba(255, 159, 64, 1)',
                fill: false
            }]
        },
    });
</script>
</body>
</html>