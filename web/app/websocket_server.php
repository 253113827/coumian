<?php
require __DIR__ . '/../vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;

class NotificationServer implements \Ratchet\MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(\Ratchet\ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(\Ratchet\ConnectionInterface $from, $msg) {
        echo "Received message: {$msg}\n";  
        try {
            $data = json_decode($msg, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo "JSON decode error: " . json_last_error_msg() . "\n";
                return;
            }
            echo "Decoded message: " . print_r($data, true) . "\n";
            
            foreach ($this->clients as $client) {
                echo "Sending to client: {$client->resourceId}\n";
                $client->send($msg);
            }
        } catch (\Exception $e) {
            echo "Error processing message: {$e->getMessage()}\n";
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

    public function broadcast($message) {
        foreach ($this->clients as $client) {
            $client->send($message);
        }
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new NotificationServer()
        )
    ),
    8081
);

echo "WebSocket server started on port 8081\n";
$server->run();
