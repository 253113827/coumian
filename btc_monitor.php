<?php
header('Content-Type: text/html; charset=utf-8');

class BTCMonitor {
    private $db;
    private $ws;
    private $okx_api_key = 'a658ad87-979c-40dc-8868-a4207015a3b2'; // 请填写您的OKX API密钥
    private $okx_secret_key = 'DD97CE2147CB121836F675CA21561538'; // 请填写您的OKX Secret密钥
    private $okx_passphrase = 'qQ123456!'; // 请填写您的OKX Passphrase

    public function __construct() {
        // 连接MySQL数据库
        $this->db = new mysqli('www.coumian.com', 'coumian', 'qq3128537', 'coumian');
        $this->db->set_charset('utf8mb4');
        
        // 初始化WebSocket连接
        $this->ws = new WebSocket\Client("ws://localhost:8080");
    }

    private function getOKXData($period) {
        $endpoint = "https://www.okx.com/api/v5/market/candles";
        $params = [
            'instId' => 'BTC-USDT',
            'bar' => $period,
            'limit' => '14'
        ];
        
        $timestamp = gmdate('Y-m-d\TH:i:s.000\Z');
        $method = 'GET';
        $requestPath = '/api/v5/market/candles?' . http_build_query($params);
        
        $sign = base64_encode(
            hash_hmac(
                'sha256',
                $timestamp . $method . $requestPath,
                $this->okx_secret_key,
                true
            )
        );
        
        $headers = [
            'OK-ACCESS-KEY: ' . $this->okx_api_key,
            'OK-ACCESS-SIGN: ' . $sign,
            'OK-ACCESS-TIMESTAMP: ' . $timestamp,
            'OK-ACCESS-PASSPHRASE: ' . $this->okx_passphrase,
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint . '?' . http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        
        if(curl_errno($ch)) {
            error_log('Curl error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        $result = json_decode($response, true);
        if (isset($result['code']) && $result['code'] !== '0') {
            error_log('OKX API error: ' . json_encode($result));
        }
        
        return $result;
    }

    private function calculateRSI($data) {
        $gains = [];
        $losses = [];
        
        // 计算价格变化
        for ($i = 1; $i < count($data); $i++) {
            $change = $data[$i][4] - $data[$i-1][4]; // 使用收盘价
            if ($change >= 0) {
                $gains[] = $change;
                $losses[] = 0;
            } else {
                $gains[] = 0;
                $losses[] = abs($change);
            }
        }
        
        // 计算平均值
        $avgGain = array_sum($gains) / 14;
        $avgLoss = array_sum($losses) / 14;
        
        // 计算RSI
        if ($avgLoss == 0) {
            return 100;
        }
        $rs = $avgGain / $avgLoss;
        return 100 - (100 / (1 + $rs));
    }

    private function addTask($title, $description) {
        $notification_time = date('Y-m-d H:i:s', strtotime('+5 seconds'));
        $sql = "INSERT INTO tasks (title, description, status, notification_time) VALUES (?, ?, 'pending', ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sss', $title, $description, $notification_time);
        
        if ($stmt->execute()) {
            // 发送WebSocket消息
            $this->ws->send(json_encode([
                'type' => 'task_added',
                'message' => '新任务已添加: ' . $title
            ]));
            return true;
        }
        return false;
    }

    public function monitor() {
        $periods = [
            '1m' => '1分钟',
            '15m' => '15分钟',
            '30m' => '30分钟',
            '4H' => '4小时'
        ];
        
        foreach ($periods as $period => $periodName) {
            usleep(500000); // 500ms延迟
            $data = $this->getOKXData($period);
            
            if (isset($data['data']) && !empty($data['data'])) {
                $rsi = $this->calculateRSI($data['data']);
                $currentPrice = $data['data'][0][4]; // 最新收盘价
                
                $status = '';
                if ($rsi > 55) {
                    $status = '超买';
                    $this->addTask(
                        "BTC {$periodName}周期RSI超买警报",
                        "当前BTC价格: \${$currentPrice}\nRSI值: " . number_format($rsi, 2) . "\n状态: {$status}"
                    );
                    
                    // 发送WebSocket消息
                    $this->ws->send(json_encode([
                        'type' => 'market_alert',
                        'message' => "BTC {$periodName}周期RSI超买: " . number_format($rsi, 2)
                    ]));
                } elseif ($rsi < 45) {
                    $status = '超卖';
                    $this->addTask(
                        "BTC {$periodName}周期RSI超卖警报",
                        "当前BTC价格: \${$currentPrice}\nRSI值: " . number_format($rsi, 2) . "\n状态: {$status}"
                    );
                    
                    // 发送WebSocket消息
                    $this->ws->send(json_encode([
                        'type' => 'market_alert',
                        'message' => "BTC {$periodName}周期RSI超卖: " . number_format($rsi, 2)
                    ]));
                }
                
                echo "{$periodName} RSI: " . number_format($rsi, 2) . " 状态: {$status}<br>";
            }
        }
    }
}

// 创建监控实例并运行
$monitor = new BTCMonitor();
$monitor->monitor();
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BTC RSI监控</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        .monitor-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .status-card {
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .status-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container monitor-container">
        <h2 class="text-center mb-4">BTC RSI监控</h2>
        <div id="statusContainer"></div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function updateStatus() {
            $.ajax({
                url: 'btc_monitor.php',
                type: 'GET',
                success: function(data) {
                    $('#statusContainer').html(data);
                }
            });
        }

        // 页面加载时立即更新一次
        updateStatus();

        // 每秒自动刷新一次
        setInterval(updateStatus, 1000);
    </script>
</body>
</html>
