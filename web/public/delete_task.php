<?php
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Task.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'])) {
    $task = new Task();
    $task->deleteTask($_POST['task_id']);
}

header('Location: tasks.php');
exit;
