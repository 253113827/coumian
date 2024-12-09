#!/bin/bash

# 远程服务器信息
REMOTE_USER="root"
REMOTE_HOST="120.55.63.87"

# 远程执行重启命令
ssh $REMOTE_USER@$REMOTE_HOST << 'EOL'
# 重启PHP 8.2
/etc/init.d/php-fpm-82 restart

# 重启Nginx
/etc/init.d/nginx restart

# 重启WebSocket服务
cd /www/wwwroot/coumian
pkill -f "php websocket_server.php"
nohup /www/server/php/82/bin/php websocket_server.php > websocket.log 2>&1 &

echo "服务重启完成！"
EOL
