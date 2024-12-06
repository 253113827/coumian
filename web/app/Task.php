<?php
class Task {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function createTask($title, $description, $notificationTime) {
        $sql = "INSERT INTO tasks (title, description, notification_time, status) 
                VALUES (:title, :description, :notification_time, 'pending')";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'title' => $title,
            'description' => $description,
            'notification_time' => $notificationTime
        ]);
    }

    public function getTasks() {
        $sql = "SELECT * FROM tasks ORDER BY notification_time ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateTaskStatus($taskId, $status) {
        $sql = "UPDATE tasks SET status = :status WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'status' => $status,
            'id' => $taskId
        ]);
    }

    public function deleteTask($taskId) {
        $sql = "DELETE FROM tasks WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $taskId]);
    }

    public function getPendingNotifications() {
        $sql = "SELECT * FROM tasks 
                WHERE status = 'pending' 
                AND notification_time <= NOW()";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
