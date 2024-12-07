<?php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Task.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $notification_date = $_POST['notification_date'] ?? '';
    $notification_time = $_POST['notification_time'] ?? '';
    
    if ($title && $notification_date && $notification_time) {
        $notification_datetime = $notification_date . ' ' . $notification_time;
        $task = new Task();
        if ($task->createTask($title, $description, $notification_datetime)) {
            header('Location: tasks.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>添加任务 - 凑面</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script>
        // 设置默认时间为当前时间
        window.onload = function() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            
            document.getElementById('notification_date').value = `${year}-${month}-${day}`;
            document.getElementById('notification_time').value = `${hours}:${minutes}:${seconds}`;

            // 连接WebSocket
            connectWebSocket();
        }

        let ws;
        function connectWebSocket() {
            ws = new WebSocket('ws://localhost:8081');
            
            ws.onopen = function() {
                document.getElementById('ws-status').textContent = 'WebSocket连接已建立';
                document.getElementById('ws-status').className = 'text-green-600';
                document.getElementById('test-notification').disabled = false;
            };
            
            ws.onmessage = function(event) {
                try {
                    const data = JSON.parse(event.data);
                    const statusDiv = document.getElementById('ws-status');
                    if (data.type === 'notification_response') {
                        statusDiv.textContent = data.message || '服务器已确认接收通知';
                        statusDiv.className = data.success ? 'text-green-600' : 'text-red-600';
                    }
                } catch (error) {
                    console.error('解析服务器消息失败:', error);
                }
            };
            
            ws.onclose = function() {
                document.getElementById('ws-status').textContent = 'WebSocket连接已断开，3秒后重试...';
                document.getElementById('ws-status').className = 'text-red-600';
                document.getElementById('test-notification').disabled = true;
                setTimeout(connectWebSocket, 3000);
            };
            
            ws.onerror = function(error) {
                document.getElementById('ws-status').textContent = 'WebSocket连接错误';
                document.getElementById('ws-status').className = 'text-red-600';
                document.getElementById('test-notification').disabled = true;
            };
        }

        function sendTestNotification() {
            if (ws && ws.readyState === WebSocket.OPEN) {
                const notification = {
                    type: 'notification',
                    title: '测试通知',
                    content: '这是一条测试通知，发送时间：' + new Date().toLocaleString(),
                    timestamp: new Date().toISOString()
                };
                try {
                    ws.send(JSON.stringify(notification));
                    // 添加发送成功提示
                    const statusDiv = document.getElementById('ws-status');
                    statusDiv.textContent = '测试通知发送成功！';
                    statusDiv.className = 'text-green-600';
                    // 3秒后恢复原状态
                    setTimeout(() => {
                        statusDiv.textContent = 'WebSocket连接已建立';
                        statusDiv.className = 'text-green-600';
                    }, 3000);
                } catch (error) {
                    const statusDiv = document.getElementById('ws-status');
                    statusDiv.textContent = '发送失败：' + error.message;
                    statusDiv.className = 'text-red-600';
                }
            } else {
                const statusDiv = document.getElementById('ws-status');
                statusDiv.textContent = 'WebSocket未连接，无法发送通知';
                statusDiv.className = 'text-red-600';
                // 尝试重新连接
                connectWebSocket();
            }
        }
    </script>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-lg mb-8">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex justify-between">
                <div class="flex space-x-7">
                    <div>
                        <a href="/" class="flex items-center py-4">
                            <span class="font-semibold text-gray-500 text-lg">凑面</span>
                        </a>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="tasks.php" class="py-2 px-4 text-gray-500 hover:text-gray-700">任务列表</a>
                    <a href="add_task.php" class="py-2 px-4 bg-blue-500 text-white rounded hover:bg-blue-600">添加任务</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4">
        <div class="bg-white rounded-lg shadow-md p-6 max-w-lg mx-auto">
            <h2 class="text-2xl font-bold mb-6">添加新任务</h2>
            
            <!-- WebSocket状态和测试按钮 -->
            <div class="mb-6 flex items-center justify-between">
                <div id="ws-status" class="text-gray-600">等待WebSocket连接...</div>
                <button 
                    id="test-notification" 
                    onclick="sendTestNotification()" 
                    disabled 
                    class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    发送测试通知
                </button>
            </div>

            <form method="POST" action="add_task.php">
                <div class="mb-4">
                    <label for="title" class="block text-gray-700 text-sm font-bold mb-2">标题</label>
                    <input type="text" id="title" name="title" required
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="mb-4">
                    <label for="description" class="block text-gray-700 text-sm font-bold mb-2">描述（可选）</label>
                    <textarea id="description" name="description"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline h-24"></textarea>
                </div>
                <div class="mb-4">
                    <label for="notification_date" class="block text-gray-700 text-sm font-bold mb-2">提醒日期</label>
                    <input type="date" id="notification_date" name="notification_date" required
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="mb-6">
                    <label for="notification_time" class="block text-gray-700 text-sm font-bold mb-2">提醒时间</label>
                    <input type="time" id="notification_time" name="notification_time" step="1" required
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="flex items-center justify-between">
                    <button type="submit"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        添加任务
                    </button>
                    <a href="tasks.php"
                        class="text-blue-500 hover:text-blue-700">
                        返回任务列表
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
