<?php
header('Content-Type: text/html; charset=utf-8');
require __DIR__ . '/../vendor/autoload.php';

use WebSocket\Client;

$config = require __DIR__ . '/../../config/config.php';

// 定义RSI设置文件路径
define('RSI_SETTINGS_FILE', __DIR__ . '/../config/rsi_settings.json');

// 读取RSI设置
function getRSISettings() {
    if (file_exists(RSI_SETTINGS_FILE)) {
        $settings = json_decode(file_get_contents(RSI_SETTINGS_FILE), true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $settings;
        }
    }
    return [
        'oversold' => 30,
        'overbought' => 70
    ];
}

// 保存RSI设置
function saveRSISettings($settings) {
    if (!is_dir(dirname(RSI_SETTINGS_FILE))) {
        mkdir(dirname(RSI_SETTINGS_FILE), 0755, true);
    }
    return file_put_contents(RSI_SETTINGS_FILE, json_encode($settings));
}

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
            if ($result['rsi'] <= 55) {  // 临时调高阈值用于测试
                error_log("检测到RSI进入超卖区间: " . $result['rsi']);
                
                $status = $result['rsi'] <= 55 ? '超卖' : '超买';
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

            // 获取RSI设置
            $rsiSettings = getRSISettings();

            // 检查RSI是否进入超卖或超买区间
            $isOversold = $rsiResult['rsi'] <= $rsiSettings['oversold'];
            $isOverbought = $rsiResult['rsi'] >= $rsiSettings['overbought'];

            if ($isOversold || $isOverbought) {
                $status = $isOversold ? '超卖' : '超买';
                error_log("检测到RSI进入{$status}区间: " . $rsiResult['rsi']);
                
                // 创建WebSocket客户端连接
                try {
                    error_log("尝试连接WebSocket服务器...");
                    $client = new Client('ws://localhost:8081', [
                        'timeout' => 10
                    ]);
                    
                    // 准备通知消息
                    $notification = [
                        'type' => 'notification',
                        'title' => "BTC RSI {$status}提醒",
                        'content' => "BTC {$period} 周期 RSI 已进入{$status}区间：" . round($rsiResult['rsi'], 2) . "\n当前价格：$" . number_format($rsiResult['price'], 2),
                        'timestamp' => time()
                    ];
                    
                    error_log("准备发送通知: " . json_encode($notification));
                    
                    // 发送通知
                    $client->send(json_encode($notification));
                    error_log("通知发送成功");
                    
                    $client->close();
                    error_log("WebSocket连接已关闭");
                } catch (Exception $e) {
                    error_log("发送WebSocket通知失败: " . $e->getMessage() . "\n" . $e->getTraceAsString());
                }
            }
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

if (isset($_GET['action']) && $_GET['action'] === 'get_rsi_settings') {
    header('Content-Type: application/json');
    try {
        $settings = getRSISettings();
        echo json_encode([
            'success' => true,
            'settings' => $settings
        ]);
    } catch (Exception $e) {
        error_log("Error in get_rsi_settings action: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'update_rsi_settings') {
    header('Content-Type: application/json');
    try {
        $oversold = floatval($_POST['oversold']);
        $overbought = floatval($_POST['overbought']);
        
        if ($oversold >= 0 && $oversold <= 100 && $overbought >= 0 && $overbought <= 100 && $oversold < $overbought) {
            $settings = [
                'oversold' => $oversold,
                'overbought' => $overbought
            ];
            if (saveRSISettings($settings)) {
                echo json_encode([
                    'success' => true
                ]);
            } else {
                throw new Exception('Failed to save settings');
            }
        } else {
            throw new Exception('Invalid RSI settings');
        }
    } catch (Exception $e) {
        error_log("Error in update_rsi_settings action: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .rsi-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .rsi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .oversold {
            background-color: #ffebee;
            border-left: 4px solid #ef5350;
        }
        .overbought {
            background-color: #e8f5e9;
            border-left: 4px solid #66bb6a;
        }
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .status-active {
            background-color: #4caf50;
        }
        .status-error {
            background-color: #f44336;
        }
        .last-update {
            font-size: 0.8rem;
            color: #666;
        }
        .header-container {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 20px 0;
            margin-bottom: 20px;
            border-radius: 10px;
        }
        .countdown {
            font-size: 0.9rem;
            color: #666;
            margin-left: 15px;
        }
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="header-container">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <h1 class="mb-0">BTC-RSI监控</h1>
                    </div>
                    <div class="col-md-4 text-center">
                        <span id="connection-status" class="status-indicator"></span>
                        <span id="connection-text">连接中...</span>
                        <span class="countdown" id="countdown">3秒后更新</span>
                    </div>
                    <div class="col-md-4 text-end">
                        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#settingsModal">
                            ⚙️ RSI设置
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row" id="rsiDataContainer">
            <?php foreach ($monitor->getPeriods() as $period): ?>
            <div class="col-md-3 mb-4">
                <div class="card rsi-card" id="rsi-card-<?php echo $period; ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="card-title mb-0"><?php echo $period; ?> 周期</h5>
                            <small class="text-muted last-update" id="last-update-<?php echo $period; ?>">更新中...</small>
                        </div>
                        <div class="card-text">
                            <div class="d-flex justify-content-between mb-2">
                                <span>价格:</span>
                                <span id="price-<?php echo $period; ?>" class="fw-bold">-</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>RSI:</span>
                                <span id="rsi-<?php echo $period; ?>" class="fw-bold">-</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- RSI设置模态框 -->
        <div class="modal fade" id="settingsModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">RSI 阈值设置</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="rsiSettingsForm">
                            <div class="mb-3">
                                <label for="oversold" class="form-label">超卖阈值 (0-100)</label>
                                <input type="number" class="form-control" id="oversold" min="0" max="100" step="1" value="30">
                                <div class="form-text">RSI低于此值时触发超卖提醒</div>
                            </div>
                            <div class="mb-3">
                                <label for="overbought" class="form-label">超买阈值 (0-100)</label>
                                <input type="number" class="form-control" id="overbought" min="0" max="100" step="1" value="70">
                                <div class="form-text">RSI高于此值时触发超买提醒</div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="button" class="btn btn-primary" id="saveSettings">保存设置</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="toast-container"></div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">BTC-RSI监控说明</h5>
                        <p>相对强弱指数（RSI）是一种动量振荡器，用于衡量资产价格变化的速度和幅度。</p>
                        <ul>
                            <li>RSI值范围：0-100</li>
                            <li>超卖区间：RSI ≤ 30，可能出现反弹机会</li>
                            <li>超买区间：RSI ≥ 70，可能出现回调风险</li>
                            <li>数据更新频率：每3秒</li>
                        </ul>
                        <div class="alert alert-info">
                            <strong>提示：</strong> 当RSI进入超买或超卖区间时，系统会自动发送通知提醒。
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const periods = <?php echo json_encode($monitor->getPeriods()); ?>;
        const initialData = <?php echo json_encode($initialData); ?>;
        let updateInterval;
        let consecutiveErrors = 0;
        const MAX_CONSECUTIVE_ERRORS = 3;
        let countdown = 3;

        function showToast(message, type = 'info') {
            const toastContainer = document.querySelector('.toast-container');
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            toastContainer.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            toast.addEventListener('hidden.bs.toast', () => toast.remove());
        }

        function updateCountdown() {
            const countdownEl = document.getElementById('countdown');
            countdownEl.textContent = `${countdown}秒后更新`;
            if (countdown <= 0) {
                countdown = 3;
                fetchRSIData();
            }
            countdown--;
        }

        function updateConnectionStatus(status) {
            const indicator = document.getElementById('connection-status');
            const text = document.getElementById('connection-text');
            
            if (status === 'active') {
                indicator.className = 'status-indicator status-active';
                text.textContent = '连接正常';
                consecutiveErrors = 0;
            } else {
                indicator.className = 'status-indicator status-error';
                text.textContent = '连接异常';
            }
        }

        function formatTimestamp(timestamp) {
            const date = new Date(timestamp * 1000);
            return date.toLocaleTimeString('zh-CN', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }

        function updateRSIDisplay(period, data) {
            const priceEl = document.getElementById(`price-${period}`);
            const rsiEl = document.getElementById(`rsi-${period}`);
            const cardEl = document.getElementById(`rsi-card-${period}`);
            const lastUpdateEl = document.getElementById(`last-update-${period}`);

            if (data && data.price && data.rsi) {
                priceEl.textContent = `${data.price.toFixed(2)} USDT`;
                rsiEl.textContent = data.rsi.toFixed(2);
                lastUpdateEl.textContent = `最后更新: ${formatTimestamp(data.timestamp)}`;

                const oversold = parseFloat(document.getElementById('oversold').value);
                const overbought = parseFloat(document.getElementById('overbought').value);

                cardEl.classList.remove('oversold', 'overbought');
                if (data.rsi <= oversold) {
                    cardEl.classList.add('oversold');
                } else if (data.rsi >= overbought) {
                    cardEl.classList.add('overbought');
                }
            }
        }

        async function fetchRSIData() {
            try {
                const response = await fetch('btc_monitor.php?action=update');
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                if (result.success) {
                    updateConnectionStatus('active');
                    
                    periods.forEach(period => {
                        if (result.data[period].success) {
                            updateRSIDisplay(period, result.data[period].data);
                        } else {
                            console.error(`Error for ${period}:`, result.data[period].error);
                        }
                    });
                } else {
                    throw new Error(result.error || '未知错误');
                }
            } catch (error) {
                console.error('Error fetching RSI data:', error);
                consecutiveErrors++;
                updateConnectionStatus('error');
                
                if (consecutiveErrors >= MAX_CONSECUTIVE_ERRORS) {
                    clearInterval(updateInterval);
                    showToast('数据更新连续失败，请刷新页面重试', 'danger');
                }
            }
        }

        function loadRSISettings() {
            fetch('btc_monitor.php?action=get_rsi_settings')
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        document.getElementById('oversold').value = result.settings.oversold;
                        document.getElementById('overbought').value = result.settings.overbought;
                    }
                })
                .catch(error => console.error('Error loading RSI settings:', error));
        }

        document.getElementById('saveSettings').addEventListener('click', function() {
            const oversold = parseFloat(document.getElementById('oversold').value);
            const overbought = parseFloat(document.getElementById('overbought').value);

            if (oversold >= 0 && oversold <= 100 && overbought >= 0 && overbought <= 100 && oversold < overbought) {
                const formData = new FormData();
                formData.append('action', 'update_rsi_settings');
                formData.append('oversold', oversold);
                formData.append('overbought', overbought);

                fetch('btc_monitor.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        showToast('RSI设置已更新', 'success');
                        bootstrap.Modal.getInstance(document.getElementById('settingsModal')).hide();
                    } else {
                        showToast(result.error || 'RSI设置更新失败', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error saving RSI settings:', error);
                    showToast('保存RSI设置失败', 'danger');
                });
            } else {
                showToast('无效的RSI设置值。超卖值必须小于超买值，且都必须在0-100之间。', 'danger');
            }
        });

        // 初始化数据
        periods.forEach(period => {
            updateRSIDisplay(period, initialData[period]);
        });

        // 加载RSI设置
        loadRSISettings();

        // 启动倒计时和数据更新
        fetchRSIData();
        setInterval(updateCountdown, 1000);
    </script>
</body>
</html>
