#!/bin/bash

# 远程服务器信息
REMOTE_USER="root"
REMOTE_HOST="120.55.63.87"

# 远程执行安装命令
ssh $REMOTE_USER@$REMOTE_HOST << 'EOL'
# 安装依赖
yum install -y openssl-devel libzip-devel

# 进入PHP源码目录
cd /www/server/php/82/src/ext

# 安装 OpenSSL 扩展
cd openssl
/www/server/php/82/bin/phpize
./configure --with-php-config=/www/server/php/82/bin/php-config
make && make install

# 安装 PDO MySQL 扩展
cd ../pdo_mysql
/www/server/php/82/bin/phpize
./configure --with-php-config=/www/server/php/82/bin/php-config
make && make install

# 安装 MySQLi 扩展
cd ../mysqli
/www/server/php/82/bin/phpize
./configure --with-php-config=/www/server/php/82/bin/php-config
make && make install

# 安装 cURL 扩展
cd ../curl
/www/server/php/82/bin/phpize
./configure --with-php-config=/www/server/php/82/bin/php-config
make && make install

# 安装 FileInfo 扩展
cd ../fileinfo
/www/server/php/82/bin/phpize
./configure --with-php-config=/www/server/php/82/bin/php-config
make && make install

# 安装 ZIP 扩展
cd ../zip
/www/server/php/82/bin/phpize
./configure --with-php-config=/www/server/php/82/bin/php-config
make && make install

# 修改PHP配置文件
echo "extension=openssl.so" > /www/server/php/82/etc/php.d/openssl.ini
echo "extension=pdo_mysql.so" > /www/server/php/82/etc/php.d/pdo_mysql.ini
echo "extension=mysqli.so" > /www/server/php/82/etc/php.d/mysqli.ini
echo "extension=curl.so" > /www/server/php/82/etc/php.d/curl.ini
echo "extension=fileinfo.so" > /www/server/php/82/etc/php.d/fileinfo.ini
echo "extension=zip.so" > /www/server/php/82/etc/php.d/zip.ini

# 重启PHP-FPM
/etc/init.d/php-fpm-82 restart

echo "PHP扩展安装完成！"
EOL
