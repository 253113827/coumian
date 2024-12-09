#!/bin/bash

# 远程服务器信息
REMOTE_USER="root"
REMOTE_HOST="120.55.63.87"

# 远程执行配置命令
ssh $REMOTE_USER@$REMOTE_HOST << 'EOL'
# 创建网站目录
mkdir -p /www/wwwroot/coumian
chown -R www:www /www/wwwroot/coumian

# 配置Nginx虚拟主机
cat > /www/server/panel/vhost/nginx/coumian.conf << 'EOF'
server {
    listen 80;
    server_name 120.55.63.87;
    root /www/wwwroot/coumian;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/tmp/php-cgi-82.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
EOF

# 重启Nginx
/etc/init.d/nginx restart

# 创建数据库
mysql -ucoumian -pqq3128537 << 'EOSQL'
CREATE DATABASE IF NOT EXISTS coumian CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EOSQL

echo "网站配置完成！"
EOL
