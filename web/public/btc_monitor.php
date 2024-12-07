<?php
header('Content-Type: text/html; charset=utf-8');
$config = require __DIR__ . '/../../config/config.php';

class BTCMonitor {
    private $config;
    protected $periods = ['1m', '15m', '30m', '1H'];

    public function __construct($config) {
        $this->config = $config;
    }

    public function getOKXData($period) {
        $url = "https://www.okx.com/api/v5/market/candles?instId=BTC-USDT&bar={$period}&limit=100";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko)'
            ]
        ]);
        
        error_log("Fetching data from OKX for period {$period}");
        error_log("URL: {$url}");
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            error_log("Curl error for period {$period}: " . curl_error($ch));
            curl_close($ch);
            return ['data' => []];
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("HTTP error {$httpCode} for period {$period}");
            error_log("Response: {$response}");
            return ['data' => []];
        }
        
        $data = json_decode($response, true);
        if (!isset($data['data']) || empty($data['data'])) {
            error_log("Empty or invalid response from OKX for period {$period}");
            error_log("Response: {$response}");
            return ['data' => []];
        }
        
        error_log("Successfully fetched " . count($data['data']) . " candles for period {$period}");
        return $data;
    }

    public function calculateRSI($data, $period = 14) {
        try {
            if (empty($data['data'])) {
                error_log("No data available for RSI calculation");
                return ['rsi' => 50, 'price' => 0];
            }

            // 获取最新价格（第一个K线的收盘价）
            $latestPrice = floatval($data['data'][0][4]);
            error_log("Latest price: {$latestPrice}");
            
            $closes = array_map(function($candle) {
                return floatval($candle[4]);
            }, $data['data']);
            $closes = array_reverse($closes);

            if (count($closes) < $period + 1) {
                error_log("Insufficient data for RSI calculation. Need " . ($period + 1) . " periods, got " . count($closes));
                return ['rsi' => 50, 'price' => $latestPrice];
            }

            $gains = [];
            $losses = [];
            
            for ($i = 1; $i < count($closes); $i++) {
                $change = $closes[$i] - $closes[$i - 1];
                $gains[] = max($change, 0);
                $losses[] = abs(min($change, 0));
            }

            $avgGain = array_sum(array_slice($gains, 0, $period)) / $period;
            $avgLoss = array_sum(array_slice($losses, 0, $period)) / $period;

            for ($i = $period; $i < count($gains); $i++) {
                $avgGain = (($avgGain * ($period - 1)) + $gains[$i]) / $period;
                $avgLoss = (($avgLoss * ($period - 1)) + $losses[$i]) / $period;
            }

            if ($avgLoss == 0) {
                error_log("Average loss is 0, returning RSI 100");
                return ['rsi' => 100, 'price' => $latestPrice];
            }

            $rs = $avgGain / $avgLoss;
            $rsi = 100 - (100 / (1 + $rs));

            error_log("Calculated RSI: {$rsi}");
            return [
                'rsi' => $rsi,
                'price' => $latestPrice
            ];
        } catch (Exception $e) {
            error_log("Error calculating RSI: " . $e->getMessage());
            return ['rsi' => 50, 'price' => 0];
        }
    }

    public function getRSIValues() {
        $results = [];
        foreach ($this->periods as $period) {
            error_log("\nProcessing period: {$period}");
            
            $data = $this->getOKXData($period);
            if (empty($data['data'])) {
                error_log("No data received for period {$period}");
                $results[$period] = ['rsi' => 50, 'price' => 0];
                continue;
            }
            
            $result = $this->calculateRSI($data);
            error_log("Period {$period} - RSI: {$result['rsi']}, Price: {$result['price']}");
            
            // 发送WebSocket通知
            if ($result['rsi'] <= 30 || $result['rsi'] >= 70) {
                $status = $result['rsi'] <= 30 ? '超卖' : '超买';
                $notification = [
                    'type' => 'btc_rsi',
                    'period' => $period,
                    'rsi' => $result['rsi'],
                    'price' => $result['price'],
                    'status' => $status
                ];
                
                error_log("Sending notification for period {$period}: " . json_encode($notification));
                
                // 使用curl发送到WebSocket服务器
                $ch = curl_init('http://localhost:8080/notify');
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notification));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_exec($ch);
                curl_close($ch);
            }
            
            $results[$period] = $result;
        }
        return $results;
    }

    public function getPeriods() {
        return $this->periods;
    }
}

function fetchKlineData($period) {
    try {
        $instId = 'BTC-USDT';
        $bar = $period;  // OKX API accepts these period values directly
        $limit = '100';
        
        $ch = curl_init();
        $url = "https://www.okx.com/api/v5/market/candles?instId={$instId}&bar={$bar}&limit={$limit}";
        
        error_log("[DEBUG] Requesting URL for period {$period}: {$url}");
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'
            ]
        ]);
        
        $startTime = microtime(true);
        $response = curl_exec($ch);
        $endTime = microtime(true);
        
        error_log("[DEBUG] API request for period {$period} took " . round($endTime - $startTime, 2) . " seconds");
        
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("Curl error for period {$period}: {$error}");
        }
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("[ERROR] API response for period {$period}: {$response}");
            throw new Exception("HTTP error {$httpCode} for period {$period}");
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['data']) || !is_array($data['data'])) {
            error_log("[ERROR] Invalid API response format for period {$period}: " . json_encode($data));
            throw new Exception("Invalid API response format for period {$period}");
        }
        
        if (empty($data['data'])) {
            error_log("[ERROR] Empty data array received for period {$period}");
            throw new Exception("No data available for period {$period}");
        }
        
        return $data['data'];
    } catch (Exception $e) {
        error_log("[ERROR] Failed to fetch kline data for period {$period}: " . $e->getMessage());
        throw $e;
    }
}

function calculateRSI($data, $period = 14) {
    try {
        if (empty($data) || count($data) < ($period + 1)) {
            throw new Exception("Insufficient data for RSI calculation. Need at least " . ($period + 1) . " candles.");
        }

        // Extract closing prices
        $closes = array_map(function($candle) {
            return floatval($candle[4]);
        }, $data);

        // Calculate price changes
        $changes = [];
        for ($i = 0; $i < count($closes) - 1; $i++) {
            $changes[] = $closes[$i] - $closes[$i + 1];
        }

        // Separate gains and losses
        $gains = array_map(function($change) { return max($change, 0); }, $changes);
        $losses = array_map(function($change) { return abs(min($change, 0)); }, $changes);

        // Calculate initial averages
        $avgGain = array_sum(array_slice($gains, 0, $period)) / $period;
        $avgLoss = array_sum(array_slice($losses, 0, $period)) / $period;

        // Calculate subsequent values using Wilder's smoothing
        for ($i = $period; $i < count($changes); $i++) {
            $avgGain = (($avgGain * ($period - 1)) + $gains[$i]) / $period;
            $avgLoss = (($avgLoss * ($period - 1)) + $losses[$i]) / $period;
        }

        if ($avgLoss == 0) {
            return $avgGain == 0 ? 50 : 100;
        }

        $rs = $avgGain / $avgLoss;
        $rsi = 100 - (100 / (1 + $rs));

        return max(0, min(100, $rsi));
    } catch (Exception $e) {
        error_log("[ERROR] RSI calculation failed: " . $e->getMessage());
        throw $e;
    }
}

function getLastPrice($data) {
    if (empty($data) || !isset($data[0][4])) {
        throw new Exception("No valid price data available");
    }
    
    $price = floatval($data[0][4]);
    if ($price <= 0) {
        throw new Exception("Invalid price value: {$price}");
    }
    
    return $price;
}

if (isset($_GET['action']) && $_GET['action'] === 'update') {
    header('Content-Type: application/json');
    try {
        $monitor = new BTCMonitor($config);
        $results = [];
        $currentTime = time();
        
        foreach ($monitor->getPeriods() as $period) {
            $data = $monitor->getOKXData($period);
            if (empty($data['data'])) {
                $results['data'][$period] = [
                    'success' => false,
                    'error' => '数据获取失败',
                    'data' => null
                ];
                continue;
            }
            
            $rsiResult = $monitor->calculateRSI($data);
            if ($rsiResult['rsi'] === 50 && $rsiResult['price'] === 0) {
                $results['data'][$period] = [
                    'success' => false,
                    'error' => 'RSI计算失败',
                    'data' => null
                ];
                continue;
            }
            
            // 获取K线时间戳
            $timestamp = isset($data['data'][0][0]) ? intval($data['data'][0][0]) / 1000 : $currentTime;
            
            $results['data'][$period] = [
                'success' => true,
                'data' => [
                    'timestamp' => $timestamp,
                    'rsi' => round($rsiResult['rsi'], 2),
                    'price' => round($rsiResult['price'], 2)
                ]
            ];
        }
        
        $results['timestamp'] = $currentTime;
        $results['success'] = true;
        
        echo json_encode($results);
    } catch (Exception $e) {
        error_log("Error in update action: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'timestamp' => time()
        ]);
    }
    exit;
}

// 初始化数据
$monitor = new BTCMonitor($config);
$initialData = [];
$currentTime = time();

foreach ($monitor->getPeriods() as $period) {
    $initialData[$period] = [
        'price' => 0,
        'rsi' => 0,
        'timestamp' => $currentTime
    ];
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BTC-RSI监控</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .status-connected {
            background-color: #34D399;
        }
        .status-disconnected {
            background-color: #EF4444;
        }
        .status-connecting {
            background-color: #F59E0B;
            animation: blink 1s infinite;
        }
        @keyframes blink {
            50% { opacity: 0.5; }
        }
        .loading {
            color: #666;
            font-style: italic;
        }
        .error {
            color: #EF4444;
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-6xl mx-auto bg-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold" id="page-title">BTC-RSI监控</h1>
                <p class="text-sm text-gray-500" id="current-time"></p>
            </div>
            <div class="flex items-center space-x-4">
                <button id="refresh-button" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                    刷新数据
                </button>
                <div class="text-sm text-gray-500">
                    <span>更新倒计时：</span>
                    <span id="next-update" class="font-mono">3</span>秒
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-500">WebSocket：</span>
                    <span id="ws-status" class="text-yellow-500">连接中...</span>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full table-auto">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-4 py-2 text-left">更新时间</th>
                        <th class="px-4 py-2 text-left">周期</th>
                        <th class="px-4 py-2 text-right">价格</th>
                        <th class="px-4 py-2 text-right">RSI</th>
                        <th class="px-4 py-2 text-center">状态</th>
                        <th class="px-4 py-2 text-center">趋势</th>
                        <th class="px-4 py-2">通知</th>
                    </tr>
                </thead>
                <tbody id="rsi-data">
                    <?php foreach ($monitor->getPeriods() as $period): ?>
                    <tr class="border-b" data-period="<?= $period ?>">
                        <td class="px-4 py-2 update-time">加载中...</td>
                        <td class="px-4 py-2"><?= $period ?></td>
                        <td class="px-4 py-2 text-right price-value">--</td>
                        <td class="px-4 py-2 text-right rsi-value">--</td>
                        <td class="px-4 py-2 text-center status">--</td>
                        <td class="px-4 py-2 text-center trend">--</td>
                        <td class="px-4 py-2 notification">--</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        let ws = null;
        let reconnectAttempts = 0;
        const maxReconnectAttempts = 5;
        const reconnectDelay = 3000;
        const updateInterval = 3; // 更新间隔（秒）
        let countdownInterval = null;
        let isUpdating = false;
        let lastUpdateTime = null;
        let lastNotificationTime = {};
        let updateTimer = null;

        // 格式化时间
        function formatDateTime(timestamp) {
            const date = new Date(timestamp * 1000);
            return date.toLocaleString('zh-CN', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            }).replace(/\//g, '-');
        }

        // 更新页面标题时间
        function updatePageTime() {
            const now = new Date();
            document.getElementById('current-time').textContent = formatDateTime(Math.floor(now.getTime() / 1000));
        }

        function startCountdown() {
            if (countdownInterval) {
                clearInterval(countdownInterval);
            }
            if (updateTimer) {
                clearTimeout(updateTimer);
            }
            
            let count = updateInterval;
            const countdownElement = document.getElementById('next-update');
            
            function updateCountdown() {
                if (count < 0) {
                    count = updateInterval;
                    forceUpdate();
                }
                
                countdownElement.textContent = Math.max(0, count);
                count--;
            }
            
            updateCountdown();
            countdownInterval = setInterval(updateCountdown, 1000);
            
            updateTimer = setTimeout(() => {
                if (!isUpdating) {
                    forceUpdate();
                }
            }, updateInterval * 1000);
        }

        function updateRowData(row, result) {
            if (!result || !result.data) {
                console.error('无效的行数据');
                return;
            }

            const period = row.getAttribute('data-period');
            
            // 更新时间
            const updateTimeCell = row.querySelector('.update-time');
            updateTimeCell.textContent = formatDateTime(result.data.timestamp);
            updateTimeCell.classList.remove('loading', 'error');
            
            // 更新价格
            const priceCell = row.querySelector('.price-value');
            if (result.data.price !== undefined) {
                priceCell.textContent = result.data.price.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                priceCell.classList.remove('loading', 'error');
            }
            
            // 更新RSI
            const rsiCell = row.querySelector('.rsi-value');
            const rsiValue = result.data.rsi;
            if (rsiValue !== undefined) {
                rsiCell.textContent = rsiValue.toFixed(2);
                rsiCell.classList.remove('loading', 'error');
                
                // 更新状态
                const statusCell = row.querySelector('.status');
                const notificationCell = row.querySelector('.notification');
                const trendCell = row.querySelector('.trend');

                // 重置所有状态
                statusCell.classList.remove('text-red-500', 'text-green-500');
                notificationCell.classList.remove('text-red-500', 'text-green-500');
                trendCell.classList.remove('text-red-500', 'text-green-500');
                
                // 更新状态和通知
                if (rsiValue >= 70) {
                    statusCell.textContent = '超买';
                    statusCell.classList.add('text-red-500');
                    notificationCell.textContent = '超买提醒';
                    notificationCell.classList.add('text-red-500');
                } else if (rsiValue <= 30) {
                    statusCell.textContent = '超卖';
                    statusCell.classList.add('text-green-500');
                    notificationCell.textContent = '超卖提醒';
                    notificationCell.classList.add('text-green-500');
                } else {
                    statusCell.textContent = '正常';
                    notificationCell.textContent = '--';
                }
                
                // 更新趋势
                if (rsiValue > 50) {
                    trendCell.textContent = '↑';
                    trendCell.classList.add('text-green-500');
                } else {
                    trendCell.textContent = '↓';
                    trendCell.classList.add('text-red-500');
                }
                
                // 检查是否需要发送通知
                if (rsiValue >= 70 || rsiValue <= 30) {
                    const now = Date.now();
                    if (!lastNotificationTime[period] || (now - lastNotificationTime[period]) > 300000) {
                        const status = rsiValue >= 70 ? '超买' : '超卖';
                        const notification = `${period}周期 RSI=${rsiValue.toFixed(2)} (${status})`;
                        sendNotification(notification);
                        lastNotificationTime[period] = now;
                        
                        // 更新通知列
                        const notificationCell = row.querySelector('.notification');
                        notificationCell.textContent = `${status}提醒`;
                        notificationCell.classList.add(status === '超买' ? 'text-red-500' : 'text-green-500');
                    }
                }
            }
        }

        function markRowAsError(row, errorMsg) {
            const cells = row.querySelectorAll('td:not(:nth-child(2))');
            cells.forEach(cell => {
                cell.textContent = errorMsg || '获取失败';
                cell.classList.remove('loading');
                cell.classList.add('error');
            });
        }

        function forceUpdate() {
            if (isUpdating) {
                return;
            }
            
            const now = Date.now();
            if (lastUpdateTime && (now - lastUpdateTime) < 1000) {
                return;
            }
            
            updateData();
        }

        function updateData() {
            if (isUpdating) {
                return;
            }

            isUpdating = true;
            
            // 设置加载状态
            document.querySelectorAll('tr[data-period]').forEach(row => {
                const cells = row.querySelectorAll('td:not(:nth-child(2))');
                cells.forEach(cell => {
                    const currentText = cell.textContent;
                    if (currentText === '获取失败' || currentText === '--') {
                        cell.textContent = '加载中...';
                        cell.classList.remove('error');
                        cell.classList.add('loading');
                    }
                });
            });

            axios.get(`btc_monitor.php?action=update&t=${Date.now()}`)
                .then(response => {
                    if (!response.data || !response.data.success) {
                        throw new Error(response.data?.error || '更新失败');
                    }

                    let hasValidData = false;
                    Object.entries(response.data.data || {}).forEach(([period, result]) => {
                        const row = document.querySelector(`tr[data-period="${period}"]`);
                        if (!row) return;
                        
                        if (result.success) {
                            hasValidData = true;
                            updateRowData(row, result);
                        } else {
                            markRowAsError(row, result.error);
                        }
                    });

                    if (!hasValidData) {
                        throw new Error('没有收到有效数据');
                    }

                    lastUpdateTime = Date.now();
                })
                .catch(error => {
                    console.error('更新失败:', error);
                    document.querySelectorAll('tr[data-period]').forEach(row => {
                        markRowAsError(row, '获取失败');
                    });
                })
                .finally(() => {
                    isUpdating = false;
                    startCountdown();
                });
        }

        function sendNotification(message) {
            if (!("Notification" in window)) {
                return;
            }

            if (Notification.permission === "granted") {
                new Notification("BTC-RSI 预警", { body: message });
            } else if (Notification.permission !== "denied") {
                Notification.requestPermission().then(permission => {
                    if (permission === "granted") {
                        new Notification("BTC-RSI 预警", { body: message });
                    }
                });
            }
        }

        function connectWebSocket() {
            if (ws && ws.readyState === WebSocket.OPEN) {
                return;
            }

            const wsStatus = document.getElementById('ws-status');
            wsStatus.textContent = '等待连接...';
            wsStatus.className = 'text-gray-500';

            try {
                ws = new WebSocket('ws://localhost:8081');

                ws.onopen = () => {
                    console.log('WebSocket已连接');
                    wsStatus.textContent = '已连接';
                    wsStatus.className = 'text-green-500';
                    reconnectAttempts = 0;
                    showToast('WebSocket连接成功', 'success');
                };

                ws.onclose = () => {
                    console.log('WebSocket连接已断开');
                    wsStatus.textContent = '已断开';
                    wsStatus.className = 'text-red-500';
                    
                    if (reconnectAttempts >= maxReconnectAttempts) {
                        wsStatus.textContent = '连接失败，请刷新页面重试';
                        showToast('WebSocket连接失败，已达到最大重试次数', 'error');
                        return;
                    }
                    
                    reconnectAttempts++;
                    const delay = Math.min(3000 * Math.pow(1.5, reconnectAttempts - 1), 10000); // 最大10秒
                    const remainingAttempts = maxReconnectAttempts - reconnectAttempts;
                    
                    wsStatus.textContent = `连接已断开，${(delay/1000).toFixed(1)}秒后第${reconnectAttempts}次重试...（剩余${remainingAttempts}次）`;
                    showToast(`WebSocket连接断开，${(delay/1000).toFixed(1)}秒后重试...`, 'warning');
                    
                    setTimeout(connectWebSocket, delay);
                };

                ws.onerror = (error) => {
                    console.error('WebSocket错误:', error);
                    wsStatus.textContent = 'WebSocket错误，等待重新连接...';
                    wsStatus.className = 'text-red-500';
                    showToast('WebSocket连接错误', 'error');
                };

                ws.onmessage = event => {
                    try {
                        console.log('收到新消息:', event.data);
                        const data = JSON.parse(event.data);
                        if (data.type === 'update') {
                            forceUpdate();
                        }
                    } catch (error) {
                        console.error('解析WebSocket消息失败:', error);
                    }
                };
            } catch (error) {
                wsStatus.textContent = 'WebSocket错误，等待重新连接...';
                wsStatus.className = 'text-red-500';
                console.error('WebSocket连接失败:', error);
                showToast('WebSocket连接错误', 'error');
            }
        }

        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg text-white fade-in ${
                type === 'success' ? 'bg-green-500' :
                type === 'error' ? 'bg-red-500' :
                type === 'warning' ? 'bg-yellow-500' :
                'bg-blue-500'
            }`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 500);
            }, 3000);
        }
        
        // 初始化
        document.addEventListener('DOMContentLoaded', () => {
            // 请求通知权限
            if ("Notification" in window && Notification.permission === "default") {
                Notification.requestPermission();
            }
            
            // 连接WebSocket
            connectWebSocket();
            
            // 更新时间
            updatePageTime();
            setInterval(updatePageTime, 1000);
            
            // 首次更新数据
            updateData();
            
            // 启动倒计时
            startCountdown();
            
            // 添加手动刷新按钮事件
            document.getElementById('refresh-button').addEventListener('click', () => {
                forceUpdate();
            });
            
            // 定期检查WebSocket连接
            setInterval(() => {
                if (!ws || ws.readyState !== WebSocket.OPEN) {
                    connectWebSocket();
                }
            }, 5000);
        });
    </script>
</body>
</html>
