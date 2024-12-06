<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Task.php';

function sendWebSocketNotification($notification) {
    $maxRetries = 3;
    $retryDelay = 3; // 3秒后重试
    $success = false;
    $attempt = 0;

    while (!$success && $attempt < $maxRetries) {
        $context = stream_context_create();
        $socket = @stream_socket_client('tcp://localhost:8080', $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
        
        if ($socket) {
            $message = json_encode([
                'title' => $notification['title'],
                'description' => $notification['description'],
                'id' => $notification['id'],
                'retry_count' => $attempt + 1
            ]);
            
            $bytesWritten = @fwrite($socket, $message);
            if ($bytesWritten !== false) {
                $success = true;
            }
            fclose($socket);
        }

        if (!$success) {
            $attempt++;
            if ($attempt < $maxRetries) {
                sleep($retryDelay);
            }
        }
    }

    return $success;
}

$task = new Task();
$pendingNotifications = $task->getPendingNotifications();

foreach ($pendingNotifications as $notification) {
    $success = sendWebSocketNotification($notification);
    if ($success) {
        $task->updateTaskStatus($notification['id'], 'completed');
        error_log("Successfully sent notification for task ID: " . $notification['id']);
    } else {
        error_log("Failed to send notification for task ID: " . $notification['id'] . " after 3 attempts");
    }
}

// 添加到crontab:
// * * * * * php /path/to/check_notifications.php
