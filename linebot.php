<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'includes/db.php'; // ต้องมีการเชื่อม DB และ table orders

$access_token = 'c2B5ABCVjCZoMTuYkuwEfd+GcInDAkJqzQg1sDFs5G9GEQ+6vCJToUQ3H9qNMAYDk7y82NuOFncFqgz9MudxOUbE5ZMZByY4ZFdgZLQfmYYHlbwZ8H3V3PVRcnpfywxaJk6LL6p5hTL6Oy5CTZRWdQdB04t89/1O/w1cDnyilFU=';

$logFile = 'line_event_log.txt';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - ==== New Request ====\n", FILE_APPEND);

// รับ POST content
$content = file_get_contents('php://input');
if (empty($content) && !empty($_POST)) {
    $content = json_encode($_POST);
}
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Raw content: " . ($content ?: '[EMPTY]') . "\n", FILE_APPEND);

if (!$content) exit("No POST content");

$events = json_decode($content, true);
if (!isset($events['events']) || !is_array($events['events'])) exit("No events found");

foreach ($events['events'] as $event) {
    $replyToken = $event['replyToken'] ?? '';

    // ตรวจสอบว่าผู้ใช้ส่งรูปภาพมา
    if ($event['type'] === 'message' && $event['message']['type'] === 'image') {
        $messageId = $event['message']['id'];

        // ดาวน์โหลดภาพจาก LINE
        $url = "https://api-data.line.me/v2/bot/message/$messageId/content";
        $headers = ["Authorization: Bearer $access_token"];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $imageData = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if (!$imageData) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - ❌ Failed to download image: $curl_error\n", FILE_APPEND);
            $messages = ["type"=>"text","text"=>"❌ ไม่สามารถดาวน์โหลดรูปได้"];
            sendReply($replyToken, $messages, $access_token);
            continue;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_buffer($finfo, $imageData);
        finfo_close($finfo);

        $ext = ($mimeType === 'image/png') ? '.png' : '.jpg';
        $tempFile = tempnam(sys_get_temp_dir(), 'qr_') . $ext;
        file_put_contents($tempFile, $imageData);

        // อ่าน QR Code ผ่าน API ฟรี
        $api_url = 'https://api.qrserver.com/v1/read-qr-code/';
        $cfile = new CURLFile($tempFile, $mimeType, basename($tempFile));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => $cfile]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);
        unlink($tempFile);

        file_put_contents($logFile, date('Y-m-d H:i:s') . " - QR API Response: $response\n", FILE_APPEND);

        $decoded = json_decode($response, true);
        $qr_text = $decoded[0]['symbol'][0]['data'] ?? '';

        if ($qr_text && preg_match('/order_id=(\d+)/', $qr_text, $matches)) {
            $order_id = intval($matches[1]);

            // ดึงข้อมูลออเดอร์จาก DB
            $stmt = $conn->prepare("SELECT * FROM orders WHERE id=?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $order = $result->fetch_assoc();
            $stmt->close();

            if ($order) {
                // สร้าง Flex Message แบบ Receipt
                $items = json_decode($order['items'], true); // สมมติเก็บ JSON ของสินค้าทุกชิ้น
                $item_boxes = [];
                $total = 0;

                foreach ($items as $item) {
                    $item_boxes[] = [
                        "type" => "box",
                        "layout" => "horizontal",
                        "contents" => [
                            ["type" => "text", "text" => $item['name'], "size"=>"sm", "color"=>"#555555", "flex"=>0],
                            ["type" => "text", "text" => "$".$item['price'], "size"=>"sm", "color"=>"#111111", "align"=>"end"]
                        ]
                    ];
                    $total += floatval($item['price']);
                }

                $messages = [
                    "type" => "flex",
                    "altText" => "รายละเอียดออเดอร์ #$order_id",
                    "contents" => [
                        "type" => "bubble",
                        "body" => [
                            "type" => "box",
                            "layout" => "vertical",
                            "contents" => array_merge(
                                [
                                    ["type"=>"text","text"=>"RECEIPT","weight"=>"bold","color"=>"#1DB446","size"=>"sm"],
                                    ["type"=>"text","text"=>"Order #$order_id","weight"=>"bold","size"=>"xl","margin"=>"md"]
                                ],
                                $item_boxes,
                                [
                                    ["type"=>"separator","margin"=>"xxl"],
                                    ["type"=>"box","layout"=>"horizontal","contents"=>[
                                        ["type"=>"text","text"=>"TOTAL","size"=>"sm","color"=>"#555555"],
                                        ["type"=>"text","text"=>"$".$total,"size"=>"sm","color"=>"#111111","align"=>"end"]
                                    ]]
                                ]
                            )
                        ]
                    ]
                ];
            } else {
                $messages = ["type"=>"text","text"=>"❌ ไม่พบข้อมูลออเดอร์ #$order_id"];
            }
        } else {
            $messages = ["type"=>"text","text"=>"❌ ไม่สามารถอ่าน QR Code ได้"];
        }

    } else {
        $messages = ["type"=>"text","text"=>"📷 กรุณาส่งรูป QR Code ของออเดอร์"];
    }

    // ส่งข้อความกลับ LINE
    sendReply($replyToken, $messages, $access_token);
}

// ฟังก์ชันส่งข้อความกลับ LINE
function sendReply($replyToken, $messages, $access_token){
    if (!$replyToken) return;
    $data = ['replyToken'=>$replyToken,'messages'=>[$messages]];
    $post = json_encode($data, JSON_UNESCAPED_UNICODE);
    $headers = ['Content-Type: application/json','Authorization: Bearer '.$access_token];

    $ch = curl_init('https://api.line.me/v2/bot/message/reply');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST,"POST");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$post);
    curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
    $result = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Reply Result: $result - Curl Error: $curl_error\n", FILE_APPEND);
}
?>