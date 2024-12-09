#!/bin/bash

# 远程服务器信息
REMOTE_USER="root"
REMOTE_HOST="120.55.63.87"

# 远程执行检查命令
ssh $REMOTE_USER@$REMOTE_HOST << 'EOL'
echo "检查PHP扩展配置..."
ls -l /www/server/php/82/lib/php/extensions/no-debug-non-zts-20220829/

echo -e "\n检查PHP配置文件..."
ls -l /www/server/php/82/etc/php.d/

echo -e "\n检查PHP模块..."
php -m

echo -e "\n测试数据库连接..."
php -r '
try {
    $pdo = new PDO("mysql:host=localhost;dbname=coumian", "coumian", "qq3128537");
    echo "PDO连接成功\n";
    
    $mysqli = new mysqli("localhost", "coumian", "qq3128537", "coumian");
    echo "MySQLi连接成功\n";
} catch (Exception $e) {
    echo "连接失败: " . $e->getMessage() . "\n";
}
'

echo "检查完成！"
EOL
