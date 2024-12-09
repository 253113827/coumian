#!/bin/bash

# 远程服务器信息
REMOTE_USER="root"
REMOTE_HOST="120.55.63.87"

# 远程执行修复命令
ssh $REMOTE_USER@$REMOTE_HOST << 'EOL'
# 安装必要的依赖
echo "正在安装依赖..."
yum install -y gcc make openssl-devel

# 检查PHP配置目录
echo "正在检查PHP配置..."
ls -la /www/server/php/82/etc/php.d/

# 下载并编译OpenSSL扩展
echo "正在下载PHP源代码..."
cd /tmp
wget https://www.php.net/distributions/php-8.2.13.tar.gz
tar xzf php-8.2.13.tar.gz
cd php-8.2.13

# 编译OpenSSL扩展
echo "正在编译OpenSSL扩展..."
cd ext/openssl
/www/server/php/82/bin/phpize
./configure --with-php-config=/www/server/php/82/bin/php-config --with-openssl
make && make install

# 确保OpenSSL配置文件存在
echo "extension=openssl.so" > /www/server/php/82/etc/php.d/openssl.ini

# 重启PHP-FPM
echo "重启PHP-FPM..."
/etc/init.d/php-fpm-82 restart

# 清理临时文件
cd /tmp
rm -rf php-8.2.13*

# 检查OpenSSL扩展是否已加载
php -m | grep openssl

# 安装 PEAR
cd /tmp
wget https://pear.php.net/go-pear.phar
php go-pear.phar

# 使用 PEAR 安装 openssl 扩展
pear install openssl

# 重新创建扩展配置文件
echo "正在重新配置PHP扩展..."
cat > /www/server/php/82/etc/php.d/mysqli.ini << 'EOF'
extension=mysqli.so
EOF

cat > /www/server/php/82/etc/php.d/pdo_mysql.ini << 'EOF'
extension=pdo_mysql.so
EOF

# 检查扩展文件
echo "正在检查扩展文件..."
ls -la /www/server/php/82/lib/php/extensions/no-debug-non-zts-20220829/

# 设置正确的权限
echo "正在设置权限..."
chmod 644 /www/server/php/82/etc/php.d/*.ini
chown root:root /www/server/php/82/etc/php.d/*.ini

# 验证扩展加载
echo "正在验证扩展..."
php -m

echo "修复完成！"
EOL
