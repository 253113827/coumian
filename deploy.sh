#!/bin/bash

# 配置信息
REMOTE_USER="root"
REMOTE_HOST="120.55.63.87"
REMOTE_DIR="/www/wwwroot/www.coumian.com"  # 宝塔面板默认网站目录
DB_NAME="coumian"
DB_USER="coumian"
DB_PASSWORD="qq3128537"

# 打包项目文件
echo "正在打包项目文件..."
cd $(dirname "$0")
zip -r coumian.zip . -x "*.git*" "*.DS_Store" "venv/*" "*.pyc"

# 上传文件到服务器
echo "正在上传文件到服务器..."
scp coumian.zip $REMOTE_USER@$REMOTE_HOST:/tmp/

# 在服务器上执行部署命令
ssh $REMOTE_USER@$REMOTE_HOST << 'ENDSSH'
    # 解压文件
    cd /tmp
    unzip -o coumian.zip -d /www/wwwroot/www.coumian.com/
    rm coumian.zip

    # 设置权限
    cd /www/wwwroot/www.coumian.com/
    chown -R www:www .
    chmod -R 755 .
    chmod -R 777 web/vendor

    # 安装 PHP 依赖
    cd web
    composer install --no-dev

    # 创建 Python 虚拟环境并安装依赖
    cd ..
    python3 -m venv venv
    source venv/bin/activate
    pip install -r requirements.txt

    # 配置 PM2
    pm2 delete coumian_websocket 2>/dev/null || true
    pm2 delete coumian_btc_monitor 2>/dev/null || true
    pm2 start web/websocket_server.php --name coumian_websocket
    pm2 start btc_rsi_monitor.py --name coumian_btc_monitor
    pm2 save

    # 重启 PHP-FPM
    /etc/init.d/php-fpm8.2 reload

    echo "部署完成！"
ENDSSH

# 清理本地临时文件
rm coumian.zip

echo "部署脚本执行完成！"
