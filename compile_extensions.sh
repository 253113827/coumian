#!/bin/bash

# 远程服务器信息
REMOTE_USER="root"
REMOTE_HOST="120.55.63.87"

# 远程执行编译命令
ssh $REMOTE_USER@$REMOTE_HOST << 'EOL'
echo "开始编译PHP扩展..."

# 安装必要的依赖
yum install -y \
    gcc \
    gcc-c++ \
    make \
    autoconf \
    openssl-devel \
    curl-devel

# 设置PHP路径
PHP_PATH=/www/server/php/82
PHP_CONFIG=$PHP_PATH/bin/php-config
PHPIZE=$PHP_PATH/bin/phpize

# 创建临时目录
TEMP_DIR=/tmp/php-ext-build
mkdir -p $TEMP_DIR
cd $TEMP_DIR

# 下载PHP源码
PHP_VERSION=$(php -r "echo PHP_VERSION;")
wget https://www.php.net/distributions/php-$PHP_VERSION.tar.gz
tar xzf php-$PHP_VERSION.tar.gz

# 编译mysqli扩展
cd php-$PHP_VERSION/ext/mysqli
$PHPIZE
./configure --with-php-config=$PHP_CONFIG
make && make install

# 编译pdo_mysql扩展
cd ../pdo_mysql
$PHPIZE
./configure --with-php-config=$PHP_CONFIG
make && make install

# 编译openssl扩展
cd ../openssl
$PHPIZE
./configure --with-php-config=$PHP_CONFIG
make && make install

# 确保配置文件存在
echo "extension=mysqli.so" > $PHP_PATH/etc/php.d/mysqli.ini
echo "extension=pdo_mysql.so" > $PHP_PATH/etc/php.d/pdo_mysql.ini
echo "extension=openssl.so" > $PHP_PATH/etc/php.d/openssl.ini

# 设置权限
chmod 644 $PHP_PATH/etc/php.d/*.ini
chown root:root $PHP_PATH/etc/php.d/*.ini

# 清理临时文件
cd /
rm -rf $TEMP_DIR

# 重启PHP-FPM
/etc/init.d/php-fpm-82 restart

# 验证安装
php -m

echo "扩展编译安装完成！"
EOL
