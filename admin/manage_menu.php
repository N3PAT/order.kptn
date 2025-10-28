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
  
  
      
if (isset($_POST['update_status'])) {
    $menu_id = $_POST['menu_id'];
    $status = $_POST['status'];

    $query = "UPDATE menu SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $status, $menu_id);
    $stmt->execute();

    $message = "อัปเดตสถานะสำเร็จ!";
}

$search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';
$stmt = $conn->prepare("SELECT * FROM menu WHERE name LIKE ?");
$stmt->bind_param("s", $search);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>จัดการสถานะสินค้า</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.2/dist/sweetalert2.min.css">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.2/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.7/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
    body {
        font-family: 'Prompt', sans-serif;
        background-color: #f8f9fa;
    }

    h2 {
        font-weight: 600;
    }

    .table th, .table td {
        vertical-align: middle;
        text-align: center;
    }

    .form-control, .btn {
        border-radius: 8px;
    }

    .btn-primary {
        background-color: #007bff;
        border: none;
    }

    .btn-primary:hover {
        background-color: #0056b3;
    }

    .navbar {
        border-radius: 0 0 12px 12px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .alert {
        border-radius: 8px;
        text-align: center;
        font-weight: 500;
    }

    @media (max-width: 768px) {
        .table th, .table td {
            font-size: 14px;
            padding: 6px;
        }

        .btn {
            font-size: 14px;
        }

        h2 {
            font-size: 22px;
        }
    }
</style>
</head>
<body>

<div class="container mt-5">
      <a href="main.php">กลับหน้าหลัก</a>
    <h2 class="mb-4">จัดการสถานะสินค้า</h2>

    <?php if (isset($message)): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
<form method="GET" class="form-inline mb-4">
    <div class="input-group" style="width: 300px;">
        <div class="input-group-prepend">
            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
        </div>
        <input type="text" name="search" class="form-control" placeholder="ค้นหาชื่อสินค้า..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        <div class="input-group-append">
            <button type="submit" class="btn btn-secondary">ค้นหา</button>
        </div>
    </div>
</form>

</form>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ชื่อสินค้า</th>
                <th>สถานะ</th>
                <th>เปลี่ยนสถานะ</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
                <td>
                    <form method="POST">
                        <input type="hidden" name="menu_id" value="<?php echo $row['id']; ?>">
                        <select name="status" class="form-control">
                            <option value="available" <?php if ($row['status'] == 'available') echo 'selected'; ?>>มีสินค้า</option>
                            <option value="out_of_stock" <?php if ($row['status'] == 'out_of_stock') echo 'selected'; ?>>หมดสินค้า</option>
                        </select>
                        <button class="btn btn-primary btn-sm mt-2" name="update_status">อัพเดต</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>