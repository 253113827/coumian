<?php
require_once __DIR__ . '/app/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "数据库连接成功！\n";
    
    // 测试查询
    $result = $db->query("SHOW TABLES");
    echo "数据库表：\n";
    while ($row = $result->fetch()) {
        print_r($row);
    }
} catch (Exception $e) {
    echo "错误：" . $e->getMessage() . "\n";
}
