<?php
header('Content-Type: text/html; charset=utf-8');

function get_data() {
    $conn = new mysqli('localhost', 'coumian', 'qq3128537', 'coumian');
    $conn->set_charset('utf8mb4');
    
    if ($conn->connect_error) {
        die("连接失败: " . $conn->connect_error);
    }
    
    // 获取任务列表
    $sql_tasks = "SELECT * FROM tasks ORDER BY created_at DESC, id DESC";
    $result_tasks = $conn->query($sql_tasks);
    $tasks = array();
    if ($result_tasks->num_rows > 0) {
        while($row = $result_tasks->fetch_assoc()) {
            $tasks[] = $row;
        }
    }
    
    // 获取统计信息
    $stats = array(
        'total_tasks' => 0,
        'pending_tasks' => 0,
        'completed_tasks' => 0
    );
    
    $sql_stats = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM tasks";
    $result_stats = $conn->query($sql_stats);
    if ($row_stats = $result_stats->fetch_assoc()) {
        $stats['total_tasks'] = $row_stats['total'];
        $stats['pending_tasks'] = $row_stats['pending'];
        $stats['completed_tasks'] = $row_stats['completed'];
    }
    
    $conn->close();
    
    return array(
        'tasks' => $tasks,
        'stats' => $stats,
        'current_time' => date('Y-m-d H:i:s')
    );
}

// 如果是AJAX请求，返回JSON数据
if(isset($_GET['action']) && $_GET['action'] == 'get_data') {
    header('Content-Type: application/json');
    echo json_encode(get_data());
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>任务监控面板</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .dashboard {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
        }
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .task-card {
            background: white;
            border-radius: 10px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .task-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .status-pending {
            background-color: #ffc107;
            color: #000;
        }
        .status-completed {
            background-color: #28a745;
            color: #fff;
        }
        #lastUpdate {
            text-align: center;
            margin-bottom: 20px;
            color: #666;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container dashboard">
        <h2 class="text-center mb-4">任务监控面板</h2>
        <div id="lastUpdate"></div>
        
        <!-- 统计信息卡片 -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card text-center">
                    <div class="stat-value" id="totalTasks">0</div>
                    <div class="stat-label">总任务数</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card text-center">
                    <div class="stat-value text-warning" id="pendingTasks">0</div>
                    <div class="stat-label">待处理任务</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card text-center">
                    <div class="stat-value text-success" id="completedTasks">0</div>
                    <div class="stat-label">已完成任务</div>
                </div>
            </div>
        </div>
        
        <!-- 任务列表 -->
        <div id="taskContainer"></div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('zh-CN', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }

        function updateDashboard() {
            $.ajax({
                url: 'tasks.php?action=get_data',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    // 更新统计信息
                    $('#totalTasks').text(data.stats.total_tasks);
                    $('#pendingTasks').text(data.stats.pending_tasks);
                    $('#completedTasks').text(data.stats.completed_tasks);
                    
                    // 更新最后刷新时间
                    $('#lastUpdate').text('最后更新: ' + formatDate(data.current_time));
                    
                    // 更新任务列表
                    const container = $('#taskContainer');
                    container.empty();
                    
                    data.tasks.forEach(task => {
                        const statusClass = task.status === 'pending' ? 'status-pending' : 'status-completed';
                        const statusText = task.status === 'pending' ? '待处理' : '已完成';
                        
                        const taskHtml = `
                            <div class="card task-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h5 class="card-title mb-0">${task.title}</h5>
                                        <span class="badge ${statusClass}">${statusText}</span>
                                    </div>
                                    <p class="card-text">${task.description}</p>
                                    <div class="text-muted">
                                        <small>通知时间: ${formatDate(task.notification_time)}</small>
                                    </div>
                                </div>
                            </div>
                        `;
                        container.append(taskHtml);
                    });
                },
                error: function(xhr, status, error) {
                    console.error('获取数据失败:', error);
                }
            });
        }

        $(document).ready(function() {
            // 页面加载时立即更新一次
            updateDashboard();

            // 每1秒自动刷新一次
            setInterval(updateDashboard, 1000);
        });
    </script>
</body>
</html>
