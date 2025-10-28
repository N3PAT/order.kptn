<?php
include '../includes/db.php'; // เชื่อมฐานข้อมูล

$access_token = '2008349477';
$secret = '0703c77f884e6e69ede2244e762742f0';

// รับ webhook body จาก LINE
$content = file_get_contents('php://input');
$events = json_decode($content, true);

if (!is_array($events['events'])) {
    exit;
}

foreach ($events['events'] as $event) {
    if ($event['type'] == 'message' && $event['message']['type'] == 'text') {
        $userMessage = $event['message']['text'];

        // ตรวจสอบว่าเป็น URL ของออเดอร์
        if (preg_match('/order_id=(\d+)/', $userMessage, $matches)) {
            $order_id = intval($matches[1]);

            // ดึงข้อมูล order จาก DB
            $stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();

            if ($order) {
                // สร้างข้อความตอบ
                $status = ucfirst($order['status']);
                $queue_query = $conn->query("SELECT order_id FROM orders WHERE status != 'completed' ORDER BY order_date ASC");
                $pos = 1;
                $queue_number = '-';
                while ($row = $queue_query->fetch_assoc()) {
                    if ($row['order_id'] == $order_id) {
                        $queue_number = $pos;
                        break;
                    }
                    $pos++;
                }

                $replyText = "ออเดอร์ #$order_id\nสถานะ: $status\nลำดับคิว: $queue_number";
            } else {
                $replyText = "ไม่พบออเดอร์ #$order_id";
            }
        } else {
            $replyText = "กรุณาส่ง QR Code URL ของออเดอร์";
        }

        // ส่งข้อความตอบกลับ
        $replyToken = $event['replyToken'];
        $messages = [
            'type' => 'text',
            'text' => $replyText
        ];

        $url = 'https://api.line.me/v2/bot/message/reply';
        $data = [
            'replyToken' => $replyToken,
            'messages' => [$messages],
        ];
        $post = json_encode($data);
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_exec($ch);
        curl_close($ch);
    }
}
?>