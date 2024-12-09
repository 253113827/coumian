#!/bin/bash

# 远程服务器信息
REMOTE_USER="root"
REMOTE_HOST="120.55.63.87"

# 远程执行配置命令
ssh $REMOTE_USER@$REMOTE_HOST << 'EOL'
# 创建网站目录结构
mkdir -p /www/wwwroot/coumian/web/config
cd /www/wwwroot/coumian

# 创建入口文件
cat > index.php << 'EOF'
<?php
require __DIR__ . '/web/config/config.php';

$config = require __DIR__ . '/web/config/config.php';
$db = new PDO(
    "mysql:host={$config['db']['host']};dbname={$config['db']['dbname']};charset={$config['db']['charset']}",
    $config['db']['username'],
    $config['db']['password']
);

echo "Coumian is running!";
EOF

# 创建配置文件
cat > web/config/config.php << 'EOF'
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

# 设置权限
chown -R www:www .
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;

echo "文件创建完成！"
EOL
