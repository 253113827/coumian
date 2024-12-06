CREATE DATABASE IF NOT EXISTS coumian CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE coumian;

CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    notification_time DATETIME NOT NULL,
    status ENUM('pending', 'completed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_notification_time (notification_time),
    INDEX idx_status (status)
);
