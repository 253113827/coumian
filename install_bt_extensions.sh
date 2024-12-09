#!/bin/bash

# 远程服务器信息
REMOTE_USER="root"
REMOTE_HOST="120.55.63.87"

# 远程执行安装命令
ssh $REMOTE_USER@$REMOTE_HOST << 'EOL'
# 使用宝塔API安装PHP扩展
cd /www/server/panel/plugin/php
python3 php_main.py install_php_lib 82 openssl,mysqli,pdo_mysql,curl,fileinfo,zip

# 等待安装完成
sleep 5

# 重启PHP-FPM
/etc/init.d/php-fpm-82 restart

# 检查PHP扩展
/www/server/php/82/bin/php -m

echo "PHP扩展安装完成！"
EOL
