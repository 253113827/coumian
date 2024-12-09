#!/bin/bash

# 远程服务器信息
REMOTE_USER="root"
REMOTE_HOST="120.55.63.87"

# 远程执行配置命令
ssh $REMOTE_USER@$REMOTE_HOST << 'EOL'
# 备份PHP配置
cp /www/server/php/82/etc/php.ini /www/server/php/82/etc/php.ini.bak

# 修改PHP配置
sed -i 's/;extension=openssl/extension=openssl/' /www/server/php/82/etc/php.ini
sed -i 's/;extension=pdo_mysql/extension=pdo_mysql/' /www/server/php/82/etc/php.ini
sed -i 's/;extension=mysqli/extension=mysqli/' /www/server/php/82/etc/php.ini

# 重启PHP-8.2
/etc/init.d/php-fpm-82 restart

# 重启Nginx
/etc/init.d/nginx restart

# 重启WebSocket服务
cd /www/wwwroot/coumian
pkill -f "php websocket_server.php"
nohup /www/server/php/82/bin/php websocket_server.php > websocket.log 2>&1 &

echo "PHP 8.2配置修复完成！"
EOL
