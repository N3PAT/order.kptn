<?php
include 'includes/db.php';

function generateCaptchaCode() {
    return str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT); // 4 หลัก
}

$verification_code = generateCaptchaCode();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $phone_number = trim($_POST['phone_number']);
    $address = trim($_POST['address']);
    $tos_accepted = isset($_POST['tos']) ? 1 : 0;
    $input_verification_code = $_POST['verification_code'];
    $correct_verification_code = $_POST['generated_verification_code'] ?? '';

    if (empty($full_name) || empty($phone_number) || empty($address) || !$tos_accepted) {
        echo "กรุณากรอกข้อมูลให้ครบถ้วน และยอมรับข้อกำหนด!";
    } elseif ($input_verification_code !== $correct_verification_code) {
        echo "รหัสยืนยันไม่ถูกต้อง!";
    } else {
        // ตรวจสอบว่าเบอร์ซ้ำหรือไม่
        $check_query = "SELECT id FROM users WHERE phone_number = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("s", $phone_number);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            echo "เบอร์โทรนี้ถูกลงทะเบียนแล้ว!";
        } else {
            // เพิ่มข้อมูลใหม่
            $insert_query = "INSERT INTO users (full_name, phone_number, address, tos_accepted, verification_code) 
                             VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("sssis", $full_name, $phone_number, $address, $tos_accepted, $input_verification_code);

            if ($insert_stmt->execute()) {
                header("Location: login.php");
                exit();
            } else {
                echo "เกิดข้อผิดพลาดในการบันทึกข้อมูล!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สมัครสมาชิก</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <!-- ลิ้งค์สไตล์และฟอนต์ต่างๆ -->
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

        .container {
            margin-top: 30px;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-control {
            border-radius: 8px;
            padding: 10px;
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            border-radius: 5px;
            padding: 10px 20px;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }

        .checkbox-label {
            margin-left: 8px;
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
        }

        .captcha-box {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            background-color: #f1f1f1;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>สมัครสมาชิก</h2>
        <form method="POST" action="register.php">
            <div class="form-group">
                <label for="full_name">ชื่อ-นามสกุล:</label>
                <input type="text" class="form-control" id="full_name" name="full_name" required>
            </div>
            <div class="form-group">
                <label for="phone_number">เบอร์โทรศัพท์:</label>
                <input type="text" class="form-control" id="phone_number" name="phone_number" required>
            </div>
            <div class="form-group">
                <label for="address">ที่อยู่:</label>
                <textarea class="form-control" id="address" name="address" required></textarea>
            </div>
            <div class="form-group">
                <input type="checkbox" id="tos" name="tos" required>
                <label class="checkbox-label" for="tos"><a href="https://drive.google.com/file/d/1zlV4TCkCiw2_RVRD7M_aNhX_2DEDeh34/view?usp=drivesdk">ยอมรับข้อกำหนดและเงื่อนไข (TOS)</a></label>
            </div>
            <!-- แสดงรหัส CAPTCHA -->
            <div class="form-group">
                <label for="verification_code">กรุณากรอกรหัส 4 หลัก:</label>
                <div class="captcha-box">
                    <?php echo $verification_code; // แสดงรหัส CAPTCHA ?>
                </div>
                <input type="text" class="form-control" id="verification_code" name="verification_code" required>
                <input type="hidden" name="generated_verification_code" value="<?php echo $verification_code; ?>">
            </div>
            <button type="submit" class="btn btn-primary">สมัครสมาชิก</button>
        </form>
                <a>เป็นสมาชิกแล้วหรือไม่</a>        <a href="login.php" >เข้าสู่ระบบ</a>
    </div>

    <script>
        // ตรวจสอบรหัส CAPTCHA
        function showDetails(name, description) {
            Swal.fire({
                title: name,
                text: description,
                icon: 'info',
                confirmButtonText: 'ปิด'
            });
        }
    </script>
</body>
</html>