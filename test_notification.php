<?php
$data = json_encode([
    'title' => '测试通知',
    'content' => '这是一条测试通知消息 ' . date('Y-m-d H:i:s')
]);

$ch = curl_init('http://localhost:8080/send_notification.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

echo $response;
?>
