#!/bin/bash

# 远程服务器信息
REMOTE_USER="root"
REMOTE_HOST="120.55.63.87"

# 远程执行配置命令
ssh $REMOTE_USER@$REMOTE_HOST << 'EOL'
# 配置Nginx
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
        fastcgi_pass unix:/tmp/php-cgi-82.sock;
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

# 设置目录权限
chown -R www:www /www/wwwroot/coumian
chmod -R 755 /www/wwwroot/coumian
mkdir -p /www/wwwroot/coumian/public/uploads
chmod -R 777 /www/wwwroot/coumian/public/uploads

# 重启服务
/etc/init.d/nginx restart
/etc/init.d/php-fpm-82 restart

# 重启WebSocket服务
cd /www/wwwroot/coumian
pkill -f "php websocket_server.php"
nohup /www/server/php/82/bin/php websocket_server.php > websocket.log 2>&1 &

echo "网站配置完成！"
EOL
