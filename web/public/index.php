<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>凑面 - 任务提醒</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-lg">
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

    <div class="container mx-auto px-4 py-8">
        <div class="text-center">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">欢迎使用凑面</h1>
            <p class="text-gray-600 mb-8">简单高效的任务提醒工具</p>
            <div class="flex justify-center space-x-4">
                <a href="tasks.php" class="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600">查看任务</a>
                <a href="add_task.php" class="bg-green-500 text-white px-6 py-3 rounded-lg hover:bg-green-600">添加任务</a>
            </div>
        </div>
    </div>

    <script>
        // WebSocket连接（后续实现）
        const ws = new WebSocket('ws://localhost:8080');
        
        ws.onmessage = function(event) {
            const notification = JSON.parse(event.data);
            // 处理通知
            if (Notification.permission === "granted") {
                new Notification(notification.title, {
                    body: notification.description
                });
            }
        };
    </script>
</body>
</html>
