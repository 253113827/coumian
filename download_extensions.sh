#!/bin/bash

# 远程服务器信息
REMOTE_USER="root"
REMOTE_HOST="120.55.63.87"

# 远程执行下载和安装命令
ssh $REMOTE_USER@$REMOTE_HOST << 'EOL'
# 创建临时目录
mkdir -p /tmp/php-ext
cd /tmp/php-ext

# 下载PHP扩展
wget https://dl.bt.cn/src/bt/php/82/openssl.so
wget https://dl.bt.cn/src/bt/php/82/mysqli.so
wget https://dl.bt.cn/src/bt/php/82/pdo_mysql.so

# 创建扩展目录
mkdir -p /www/server/php/82/lib/php/extensions/no-debug-non-zts-20220829/

# 复制扩展文件
cp *.so /www/server/php/82/lib/php/extensions/no-debug-non-zts-20220829/

# 创建配置文件目录
mkdir -p /www/server/php/82/etc/php.d/

# 创建扩展配置文件
echo "extension=openssl.so" > /www/server/php/82/etc/php.d/openssl.ini
echo "extension=mysqli.so" > /www/server/php/82/etc/php.d/mysqli.ini
echo "extension=pdo_mysql.so" > /www/server/php/82/etc/php.d/pdo_mysql.ini

# 设置权限
chmod 644 /www/server/php/82/lib/php/extensions/no-debug-non-zts-20220829/*.so
chmod 644 /www/server/php/82/etc/php.d/*.ini

# 重启PHP-FPM
/etc/init.d/php-fpm-82 restart

# 清理临时文件
rm -rf /tmp/php-ext

echo "PHP扩展安装完成！"
EOL
