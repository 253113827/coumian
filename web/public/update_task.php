<?php
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Task.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id']) && isset($_POST['status'])) {
    $task = new Task();
    $task->updateTaskStatus($_POST['task_id'], $_POST['status']);
}

header('Location: tasks.php');
exit;
