#!/bin/bash

# 远程服务器信息
REMOTE_USER="root"
REMOTE_HOST="120.55.63.87"
REMOTE_PATH="/www/wwwroot/coumian"

# 同步项目文件到服务器
rsync -avz --exclude '.git' --exclude 'node_modules' --exclude 'vendor' \
    /Users/sxh/coumian/coumian/coumian/ \
    ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}/

# 远程执行配置命令
ssh $REMOTE_USER@$REMOTE_HOST << 'EOL'
cd /www/wwwroot/coumian

# 设置目录权限
chown -R www:www .
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;

# 配置数据库连接
cat > /www/wwwroot/coumian/web/config/config.php << 'EOF'
<?php
return [
    'db' => [
        'host' => 'localhost',
        'dbname' => 'coumian',
        'username' => 'coumian',
        'password' => 'qq3128537',
        'charset' => 'utf8mb4'
    ],
    'site' => [
        'url' => 'http://120.55.63.87',
        'name' => 'Coumian'
    ]
];
EOF

echo "项目部署完成！"
EOL
