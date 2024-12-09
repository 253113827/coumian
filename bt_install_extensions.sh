#!/bin/bash

# 远程服务器信息
REMOTE_USER="root"
REMOTE_HOST="120.55.63.87"

# 远程执行安装命令
ssh $REMOTE_USER@$REMOTE_HOST << 'EOL'
# 使用bt命令安装PHP扩展
cd /www/server/panel/plugin/php
python3 php_main.py install_php_lib 82 mysqli,pdo_mysql,openssl

# 重启PHP-FPM
/etc/init.d/php-fpm-82 restart

# 检查PHP配置
php --ini

# 验证扩展安装
echo "检查PHP扩展..."
php -m | grep -E "mysqli|pdo_mysql|openssl"

# 测试数据库连接
echo "测试数据库连接..."
php -r '
try {
    $db = new PDO("mysql:host=localhost;dbname=coumian", "coumian", "qq3128537");
    echo "数据库连接成功！\n";
} catch (PDOException $e) {
    echo "数据库连接失败: " . $e->getMessage() . "\n";
}
'

echo "扩展安装和测试完成！"
EOL
