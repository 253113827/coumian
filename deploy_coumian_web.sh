#!/bin/bash

# 设置变量
REMOTE_USER="root"
REMOTE_HOST="8.210.203.136"
REMOTE_PASS="qQ121676463"
REMOTE_PATH="/www/wwwroot/www.shatangli.com"
LOCAL_PATH="/Users/sxh/coumian/coumian/coumian/web"

# 创建远程目录
sshpass -p "${REMOTE_PASS}" ssh -o StrictHostKeyChecking=no ${REMOTE_USER}@${REMOTE_HOST} "mkdir -p ${REMOTE_PATH}"

# 同步web目录到远程服务器
sshpass -p "${REMOTE_PASS}" rsync -avz --delete \
    --exclude='.git/' \
    --exclude='.DS_Store' \
    --exclude='*.log' \
    "${LOCAL_PATH}/" "${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}/"

# 设置文件权限
sshpass -p "${REMOTE_PASS}" ssh -o StrictHostKeyChecking=no ${REMOTE_USER}@${REMOTE_HOST} "
    chown -R www:www ${REMOTE_PATH}
    chmod -R 755 ${REMOTE_PATH}
    find ${REMOTE_PATH} -type f -exec chmod 644 {} \;
"

# 配置 Nginx
sshpass -p "${REMOTE_PASS}" ssh -o StrictHostKeyChecking=no ${REMOTE_USER}@${REMOTE_HOST} "
cat > /www/server/panel/vhost/nginx/www.shatangli.com.conf << 'EOL'
server {
    listen 80;
    server_name www.shatangli.com shatangli.com;
    root ${REMOTE_PATH};
    
    location / {
        index index.html index.htm index.php;
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/tmp/php-cgi-82.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ ^/websocket {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection \"upgrade\";
        proxy_set_header Host \$host;
    }
}
EOL"

# 重启 Nginx
sshpass -p "${REMOTE_PASS}" ssh -o StrictHostKeyChecking=no ${REMOTE_USER}@${REMOTE_HOST} "/etc/init.d/nginx restart"

echo "部署完成！"
