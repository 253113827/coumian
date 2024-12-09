#!/bin/bash

# 远程服务器信息
REMOTE_USER="root"
REMOTE_HOST="120.55.63.87"
REMOTE_PATH="/www/wwwroot/coumian"

# 创建临时目录
TMP_DIR=$(mktemp -d)
cp -r . $TMP_DIR/
rm -rf $TMP_DIR/.git $TMP_DIR/node_modules $TMP_DIR/vendor 2>/dev/null

# 使用scp上传文件
scp -r $TMP_DIR/* ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}/

# 清理临时目录
rm -rf $TMP_DIR

# 远程执行配置命令
ssh $REMOTE_USER@$REMOTE_HOST << 'EOL'
cd /www/wwwroot/coumian

# 创建必要的目录
mkdir -p web/config

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
