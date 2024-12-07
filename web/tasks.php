<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$host = 'www.coumian.com';
$user = 'coumian';
$password = 'qq3128537';
$database = 'coumian';

try {
    $conn = new PDO("mysql:host=$host;dbname=$database", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $query = "SELECT id, title, description, notification_time, status 
              FROM tasks 
              WHERE notification_sent = 0 
              ORDER BY notification_time DESC 
              LIMIT 10";
              
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($tasks);
    
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
