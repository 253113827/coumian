#!/bin/bash

# 配置信息
REMOTE_USER="root"
REMOTE_HOST="120.55.63.87"
REMOTE_PASS="qq3128537"

# 安装 sshpass（如果需要）
if ! command -v sshpass &> /dev/null; then
    brew install sshpass
fi

# 复制 SSH 公钥到服务器
sshpass -p "$REMOTE_PASS" ssh-copy-id -o StrictHostKeyChecking=no $REMOTE_USER@$REMOTE_HOST

# 测试 SSH 连接
ssh -o StrictHostKeyChecking=no $REMOTE_USER@$REMOTE_HOST "echo 'SSH 连接测试成功！'"

echo "SSH 设置完成！"
