#!/bin/bash

# 远程服务器信息
REMOTE_USER="root"
REMOTE_HOST="120.55.63.87"

# 远程执行测试命令
ssh $REMOTE_USER@$REMOTE_HOST << 'EOL'
# 测试PHP配置
echo "Testing PHP configuration..."
php -v
php -m

# 测试数据库连接
echo -e "\nTesting database connection..."
php -r '
try {
    $db = new PDO("mysql:host=localhost;dbname=coumian", "coumian", "qq3128537");
    echo "Database connection successful!\n";
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}
'

# 测试网站访问
echo -e "\nTesting website access..."
curl -I http://120.55.63.87

echo -e "\nInstallation test completed!"
EOL
