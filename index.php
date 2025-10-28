<?php include 'includes/db.php'; ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สั่งอาหารออนไลน์</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <header>
        <h1>ยินดีต้อนรับสู่ร้านอาหารออนไลน์</h1>
        <a href="menu.php">ดูเมนูอาหาร</a>
    </header>

    <main>
        <h2>เมนูขายดี</h2>
        <div class="menu-container">
            <?php
            $query = "SELECT * FROM menu ORDER BY sold DESC LIMIT 5";
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()):
            ?>
                <div class="menu-item">
                    <img src="uploads/<?php echo $row['image']; ?>" alt="<?php echo $row['name']; ?>">
                    <h3><?php echo $row['name']; ?></h3>
                    <p><?php echo $row['price']; ?> พอยต์</p>
                    <button onclick="addToCart(<?php echo $row['id']; ?>)">เพิ่มลงตะกร้า</button>
                </div>
            <?php endwhile; ?>
        </div>
    </main>

    <script>
    function addToCart(menuId) {
        Swal.fire("เพิ่มสินค้าแล้ว!", "สินค้าถูกเพิ่มลงในตะกร้า", "success");
    }
    </script>
</body>
</html>