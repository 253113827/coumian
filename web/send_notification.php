<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// 获取POST数据
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    $data = $_POST;
}

// 连接数据库
$host = 'www.coumian.com';
$user = 'coumian';
$password = 'qq3128537';
$database = 'coumian';

try {
    $conn = new PDO("mysql:host=$host;dbname=$database", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 更新任务状态为已完成
    if (isset($data['task_id'])) {
        $query = "UPDATE tasks SET status = 'completed' WHERE id = :task_id";
        $stmt = $conn->prepare($query);
        $stmt->execute(['task_id' => $data['task_id']]);
    }
    
    // 返回成功响应
    echo json_encode([
        'success' => true,
        'message' => 'Notification received and processed',
        'data' => $data
    ]);
    
} catch(PDOException $e) {
    // 返回错误响应
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
