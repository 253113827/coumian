<?php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Task.php';

$task = new Task();
$tasks = $task->getTasks();
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>任务列表 - 凑面</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script>
        let ws;
        let checkingTasks = new Set();  // 用于跟踪正在检查的任务
        let initialLoad = true;  // 标记是否是页面初始加载

        function connectWebSocket() {
            ws = new WebSocket('ws://localhost:8081');
            
            ws.onopen = function() {
                console.log('WebSocket连接已建立');
            };
            
            ws.onclose = function() {
                console.log('WebSocket连接已断开，3秒后重试...');
                setTimeout(connectWebSocket, 3000);
            };
            
            ws.onerror = function(error) {
                console.error('WebSocket错误:', error);
            };
        }

        function sendNotification(taskId, title, description, status) {
            // 如果是页面初始加载且任务已完成，不发送通知
            if (initialLoad && status === 'completed') {
                return;
            }

            // 如果是页面初始加载且任务未完成但已过期，也不发送通知
            if (initialLoad && status === 'pending') {
                const taskElement = document.querySelector(`[data-task-id="${taskId}"]`);
                if (taskElement) {
                    const targetTime = new Date(taskElement.dataset.countdown).getTime();
                    if (targetTime < Date.now()) {
                        return;
                    }
                }
            }

            if (ws && ws.readyState === WebSocket.OPEN && !checkingTasks.has(taskId)) {
                checkingTasks.add(taskId);  // 标记任务正在检查
                const notification = {
                    type: 'notification',
                    title: title,
                    content: description,
                    timestamp: new Date().toISOString()
                };
                ws.send(JSON.stringify(notification));
                console.log('已发送通知:', notification);

                // 更新任务状态为已完成
                fetch('update_task.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `task_id=${taskId}&status=completed`
                }).then(response => {
                    if (response.ok) {
                        console.log('任务状态已更新为已完成');
                        // 更新页面上的状态显示
                        const statusElement = document.querySelector(`[data-status-id="${taskId}"]`);
                        if (statusElement) {
                            statusElement.textContent = '已完成';
                            statusElement.className = 'px-2 py-1 rounded text-sm bg-green-200 text-green-800';
                        }
                        // 更新操作按钮
                        const actionButton = document.querySelector(`[data-action-id="${taskId}"]`);
                        if (actionButton) {
                            actionButton.textContent = '标记为未完成';
                        }
                    }
                }).catch(error => {
                    console.error('更新任务状态失败:', error);
                });
            }
        }

        function updateCountdowns() {
            const times = document.querySelectorAll('[data-countdown]');
            times.forEach(time => {
                const taskId = time.dataset.taskId;
                const title = time.dataset.title;
                const description = time.dataset.description;
                const status = time.dataset.status;
                const targetTime = new Date(time.dataset.countdown).getTime();
                const now = new Date().getTime();
                const diff = targetTime - now;
                
                if (diff <= 0) {
                    time.textContent = '已到时';
                    // 发送通知
                    if (status === 'pending') {  // 只对未完成的任务发送通知
                        sendNotification(taskId, title, description, status);
                    }
                } else {
                    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((diff % (1000 * 60)) / 1000);
                    
                    let countdown = '';
                    if (days > 0) countdown += `${days}天 `;
                    if (hours > 0) countdown += `${hours}小时 `;
                    if (minutes > 0) countdown += `${minutes}分钟 `;
                    countdown += `${seconds}秒`;
                    
                    time.textContent = countdown;
                }
            });

            // 第一次更新完成后，取消初始加载标记
            if (initialLoad) {
                initialLoad = false;
            }
        }
        
        // 连接WebSocket
        connectWebSocket();
        
        // 更新倒计时
        setInterval(updateCountdowns, 1000);
        document.addEventListener('DOMContentLoaded', updateCountdowns);
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
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold mb-6">任务列表</h2>
            <?php if (empty($tasks)): ?>
                <p class="text-gray-500 text-center">暂无任务</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="px-4 py-2 text-left">标题</th>
                                <th class="px-4 py-2 text-left">描述</th>
                                <th class="px-4 py-2 text-left">提醒时间</th>
                                <th class="px-4 py-2 text-left">倒计时</th>
                                <th class="px-4 py-2 text-left">状态</th>
                                <th class="px-4 py-2 text-left">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $task): ?>
                                <tr class="border-b">
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($task['title']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($task['description']); ?></td>
                                    <td class="px-4 py-2"><?php echo $task['notification_time']; ?></td>
                                    <td class="px-4 py-2">
                                        <span 
                                            data-countdown="<?php echo $task['notification_time']; ?>"
                                            data-task-id="<?php echo $task['id']; ?>"
                                            data-title="<?php echo htmlspecialchars($task['title']); ?>"
                                            data-description="<?php echo htmlspecialchars($task['description']); ?>"
                                            data-status="<?php echo $task['status']; ?>"
                                        >
                                            计算中...
                                        </span>
                                    </td>
                                    <td class="px-4 py-2">
                                        <span 
                                            class="px-2 py-1 rounded text-sm <?php echo $task['status'] === 'completed' ? 'bg-green-200 text-green-800' : 'bg-yellow-200 text-yellow-800'; ?>"
                                            data-status-id="<?php echo $task['id']; ?>"
                                        >
                                            <?php echo $task['status'] === 'completed' ? '已完成' : '待处理'; ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2">
                                        <form method="POST" action="update_task.php" class="inline">
                                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                            <input type="hidden" name="status" value="<?php echo $task['status'] === 'completed' ? 'pending' : 'completed'; ?>">
                                            <button 
                                                type="submit" 
                                                class="text-blue-500 hover:text-blue-700 mr-2"
                                                data-action-id="<?php echo $task['id']; ?>"
                                            >
                                                <?php echo $task['status'] === 'completed' ? '标记为未完成' : '标记为完成'; ?>
                                            </button>
                                        </form>
                                        <form method="POST" action="delete_task.php" class="inline">
                                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                            <button type="submit" class="text-red-500 hover:text-red-700" onclick="return confirm('确定要删除这个任务吗？')">
                                                删除
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
