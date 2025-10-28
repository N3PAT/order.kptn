<?php
session_start();
include 'includes/db.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ดึงข้อมูลผู้ใช้จาก session
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$username = $user['full_name'];

// การลบสินค้าออกจากตะกร้า
if (isset($_POST['remove_from_cart'])) {
    $menu_item_id_to_remove = $_POST['remove_item'];
    if (isset($_SESSION['cart'][$menu_item_id_to_remove])) {
        unset($_SESSION['cart'][$menu_item_id_to_remove]);
        $_SESSION['remove_success'] = true;
    }
}

$total = 0;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ตะกร้าของคุณ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.2/dist/sweetalert2.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"/>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.7/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.2/dist/sweetalert2.all.min.js"></script>

    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #f8f9fa;
        }

        .navbar {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: flex-start;
            align-items: center;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        .cart-table td, .cart-table th {
            padding: 10px;
            vertical-align: middle;
        }

        .cart-table th {
            text-align: center;
        }

        .cart-table td {
            text-align: center;
        }

        .cart-item h5 {
            margin-left: 10px;
        }

        .cart-item .btn-remove {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
        }

        .cart-item .btn-remove:hover {
            background-color: #c82333;
        }

        .cart-summary {
            margin-top: 20px;
        }

        @media (max-width: 767px) {
            .cart-item {
                flex-direction: column;
                align-items: flex-start;
            }
            .cart-item h5 {
                margin-left: 0;
            }
            .cart-table td, .cart-table th {
                padding: 8px;
            }
            .cart-summary {
                text-align: center;
            }
        }
    </style>
</head>
<body>

<!-- แถบด้านบนที่แสดงชื่อผู้ใช้ -->
<nav class="navbar">
    <div class="container d-flex justify-content-between">
        <a href="menu.php" class="navbar-brand">เมนูอาหาร</a>
        <div class="user-info">
            สวัสดี, <?php echo htmlspecialchars($username); ?> | <a href="logout.php">ออกจากระบบ</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2 class="text-center mb-4"><i class="fas fa-shopping-cart"></i> ตะกร้าของคุณ</h2>

    <!-- แสดงรายการสินค้าในตะกร้า -->
    <table class="table cart-table">
        <thead>
            <tr>
                <th>ชื่อสินค้า</th>
                <th>ราคา</th>
                <th>จำนวน</th>
                <th>รวม</th>
                <th>ลบ</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
                foreach ($_SESSION['cart'] as $menu_item_id => $item) {
                    $subtotal = $item['price'] * $item['quantity'];
                    $total += $subtotal;
                    echo "<tr class='cart-item'>
                        <td><h5>{$item['name']}</h5>";
                    
if (!empty($item['extra'])) {
    echo "<p><small>เพิ่ม: " . implode(", ", $item['extra']) . "</small></p>";
}
                    echo "</td>
                        <td>{$item['price']} บาท</td>
                        <td>{$item['quantity']}</td>
                        <td>{$subtotal} บาท</td>
                        <td>
                            <form method='POST' action=''>
                                <input type='hidden' name='remove_item' value='" . $menu_item_id . "'>
                                <button type='submit' name='remove_from_cart' class='btn-remove'>
                                    <i class='fas fa-trash'></i> ลบ
                                </button>
                            </form>
                        </td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='6' class='text-center'>ตะกร้าของคุณว่างเปล่า</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <!-- ยอดรวม -->
    <div class="cart-summary">
        <h4>ยอดรวม: <?php echo $total; ?> บาท</h4>
        <form action="checkout.php" method="POST">
            <button type="submit" class="btn btn-success btn-block" <?php echo $total == 0 ? 'disabled' : ''; ?>>ไปที่หน้าชำระเงิน</button>
        </form>
    </div>
</div>

<!-- การแจ้งเตือนเมื่อสินค้าถูกลบ -->
<?php if (isset($_SESSION['remove_success']) && $_SESSION['remove_success']): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'สินค้าถูกลบออกจากตะกร้าแล้ว',
            showConfirmButton: false,
            timer: 1500
        });
    </script>
    <?php unset($_SESSION['remove_success']); ?>
<?php endif; ?>

</body>
</html>