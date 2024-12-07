<?php
require 'vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\Server as Reactor;

class WebSocketServer implements \Ratchet\MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(\Ratchet\ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(\Ratchet\ConnectionInterface $from, $msg) {
        try {
            $data = json_decode($msg, true);
            if (!$data) {
                throw new \Exception('Invalid JSON message');
            }

            if ($data['type'] === 'ping') {
                // 处理心跳消息
                $response = json_encode([
                    'type' => 'pong',
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                $from->send($response);
                return;
            }

            if ($data['type'] === 'notification') {
                // 广播通知给所有连接的客户端
                foreach ($this->clients as $client) {
                    if ($client !== $from) {
                        $client->send($msg);
                    }
                }
                
                // 发送确认消息给发送者
                $response = json_encode([
                    'type' => 'notification_response',
                    'success' => true,
                    'message' => '通知已成功发送给其他客户端',
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                $from->send($response);
                
                echo "Notification sent at " . date('Y-m-d H:i:s') . "\n";
            }
        } catch (\Exception $e) {
            // 发送错误响应
            $errorResponse = json_encode([
                'type' => 'notification_response',
                'success' => false,
                'message' => '处理消息时出错：' . $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            $from->send($errorResponse);
            
            echo "Error processing message: " . $e->getMessage() . "\n";
        }
    }

    public function onClose(\Ratchet\ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(\Ratchet\ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

$loop = Factory::create();
$socket = new Reactor('0.0.0.0:8081', $loop);
$server = new IoServer(
    new HttpServer(
        new WsServer(
            new WebSocketServer()
        )
    ),
    $socket,
    $loop
);

echo "WebSocket server running on port 8081...\n";
$server->run();
