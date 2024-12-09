#!/bin/bash

# 远程服务器信息
REMOTE_USER="root"
REMOTE_HOST="120.55.63.87"

# 远程执行安装命令
ssh $REMOTE_USER@$REMOTE_HOST << 'EOL'
# 安装PHP扩展
cd /www/server/php/74/src/ext/
/www/server/php/74/bin/phpize
./configure --with-php-config=/www/server/php/74/bin/php-config
make && make install

# 安装必要的扩展
cd /www/server
bt install php74-openssl
bt install php74-pdo
bt install php74-mysql
bt install php74-mysqli
bt install php74-curl
bt install php74-fileinfo

# 重启PHP服务
/etc/init.d/php-fpm-74 restart

# 重启WebSocket服务
cd /www/wwwroot/coumian
pkill -f "php websocket_server.php"
nohup php websocket_server.php > websocket.log 2>&1 &

echo "PHP扩展安装完成！"
EOL
