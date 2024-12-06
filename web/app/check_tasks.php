<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Task.php';

use Ratchet\Client\WebSocket;
use React\EventLoop\Factory;
use Ratchet\Client\Connector as ClientConnector;

$loop = Factory::create();
$connector = new ClientConnector($loop);
$task = new Task();

// 连接到 WebSocket 服务器
$connector('ws://localhost:8081')->then(function(WebSocket $conn) use ($task, $loop) {
    echo "Connected to WebSocket server\n";
    
    // 每秒检查一次任务
    $loop->addPeriodicTimer(1, function() use ($conn, $task) {
        $pendingTasks = $task->getPendingNotifications();
        
        foreach ($pendingTasks as $taskData) {
            // 发送通知
            $notification = [
                'type' => 'notification',
                'title' => $taskData['title'],
                'content' => $taskData['description'],
                'timestamp' => date('c')
            ];
            
            $conn->send(json_encode($notification));
            echo "Sent notification for task: {$taskData['title']}\n";
            
            // 更新任务状态为已完成
            $task->updateTaskStatus($taskData['id'], 'completed');
        }
    });
    
}, function ($e) {
    echo "Could not connect: {$e->getMessage()}\n";
});

$loop->run();
