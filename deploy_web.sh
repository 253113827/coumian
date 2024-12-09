#!/bin/bash

# 远程服务器信息
REMOTE_USER="root"
REMOTE_HOST="120.55.63.87"
WEB_ROOT="/www/wwwroot/coumian"

# 上传项目文件
echo "正在上传项目文件..."
scp -r web/* $REMOTE_USER@$REMOTE_HOST:$WEB_ROOT/

# 远程执行配置命令
ssh $REMOTE_USER@$REMOTE_HOST << 'EOL'
# 设置目录权限
chown -R www:www /www/wwwroot/coumian
chmod -R 755 /www/wwwroot/coumian
mkdir -p /www/wwwroot/coumian/public/uploads
chmod -R 777 /www/wwwroot/coumian/public/uploads

# 配置PHP
cd /www/server/php/74/etc/
cp php.ini php.ini.bak
sed -i 's/;extension=openssl/extension=openssl/' php.ini
sed -i 's/;extension=pdo_mysql/extension=pdo_mysql/' php.ini

# 重启PHP
/etc/init.d/php-fpm-74 restart

# 创建Nginx配置
cat > /www/server/panel/vhost/nginx/coumian.conf << 'EOLNGINX'
server {
    listen 80;
    server_name 120.55.63.87;
    root /www/wwwroot/coumian/public;
    
    location / {
        index index.php index.html;
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/tmp/php-cgi-74.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location /ws {
        proxy_pass http://localhost:8081;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
    }
}
EOLNGINX

# 重启Nginx
/etc/init.d/nginx restart

# 停止现有的WebSocket服务器（如果有）
pkill -f "php websocket_server.php"

# 启动WebSocket服务器
cd /www/wwwroot/coumian
nohup php websocket_server.php > websocket.log 2>&1 &

echo "部署完成！"
EOL
