#!/bin/bash

# 远程服务器信息
REMOTE_USER="root"
REMOTE_HOST="120.55.63.87"

# 远程执行安装命令
ssh $REMOTE_USER@$REMOTE_HOST << 'EOL'
cd /www/server/php/82/src/ext/fileinfo
phpize
./configure --with-php-config=/www/server/php/82/bin/php-config
make && make install

# 创建配置文件
echo "extension=fileinfo.so" > /www/server/php/82/etc/php.d/fileinfo.ini

# 重启PHP-FPM
/etc/init.d/php-fpm-82 restart

# 验证安装
php -m | grep fileinfo

echo "Fileinfo 扩展安装完成！"
EOL
