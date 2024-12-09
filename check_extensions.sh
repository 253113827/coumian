#!/bin/bash

# 远程服务器信息
REMOTE_USER="root"
REMOTE_HOST="120.55.63.87"

# 远程执行检查命令
ssh $REMOTE_USER@$REMOTE_HOST << 'EOL'
echo "检查PHP版本和扩展..."
/www/server/php/82/bin/php -v
echo -e "\n已加载的扩展:"
/www/server/php/82/bin/php -m

echo -e "\n检查PHP-FPM状态..."
/etc/init.d/php-fpm-82 status

echo -e "\n检查Nginx配置..."
nginx -t

echo -e "\n检查WebSocket服务..."
ps aux | grep websocket_server.php

echo "检查完成！"
EOL
