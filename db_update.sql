-- 添加 notification_sent 字段到 tasks 表
ALTER TABLE tasks 
ADD COLUMN notification_sent TINYINT(1) DEFAULT 0 AFTER status;
