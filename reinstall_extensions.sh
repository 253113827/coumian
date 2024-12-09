#!/bin/bash

# 远程服务器信息
REMOTE_USER="root"
REMOTE_HOST="120.55.63.87"

# 远程执行安装命令
ssh $REMOTE_USER@$REMOTE_HOST << 'EOL'
# 安装必要的依赖
yum install -y wget curl openssl-devel

# 下载宝塔面板PHP扩展安装脚本
wget -O install_php_ext.sh http://download.bt.cn/install/0/php_ext.sh
chmod +x install_php_ext.sh

# 安装PHP扩展
./install_php_ext.sh install 82 mysqli
./install_php_ext.sh install 82 pdo_mysql
./install_php_ext.sh install 82 openssl

# 重启PHP-FPM
/etc/init.d/php-fpm-82 restart

# 验证安装
php -m

echo "扩展重新安装完成！"
EOL
