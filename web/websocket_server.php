<?php
require 'vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\SecureServer;
use React\Socket\Server;

class WebSocketServer implements \Ratchet\MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        echo "WebSocket服务器初始化完成\n";
    }

    public function onOpen(\Ratchet\ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "新的连接建立! (ID: {$conn->resourceId})\n";
        echo "当前连接数: " . count($this->clients) . "\n";
    }

    public function onMessage(\Ratchet\ConnectionInterface $from, $msg) {
        echo "收到消息: " . $msg . "\n";
        $count = 0;
        foreach ($this->clients as $client) {
            $client->send($msg);
            $count++;
        }
        echo "消息已转发给 {$count} 个客户端\n";
    }

    public function onClose(\Ratchet\ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "连接断开 (ID: {$conn->resourceId})\n";
        echo "当前连接数: " . count($this->clients) . "\n";
    }

    public function onError(\Ratchet\ConnectionInterface $conn, \Exception $e) {
        echo "发生错误: {$e->getMessage()}\n";
        echo "错误堆栈: {$e->getTraceAsString()}\n";
        $conn->close();
    }
}

// 创建WebSocket服务器
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new WebSocketServer()
        )
    ),
    8081
);

echo "WebSocket服务器启动在端口 8081\n";
$server->run();
