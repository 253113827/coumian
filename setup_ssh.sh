#!/bin/bash

# 配置信息
REMOTE_USER="root"
REMOTE_HOST="120.55.63.87"

# 检查是否存在 SSH 密钥
if [ ! -f ~/.ssh/id_rsa ]; then
    ssh-keygen -t rsa -b 4096 -N "" -f ~/.ssh/id_rsa
fi

# 复制 SSH 公钥到服务器
ssh-copy-id -i ~/.ssh/id_rsa.pub $REMOTE_USER@$REMOTE_HOST

echo "SSH 设置完成！"
