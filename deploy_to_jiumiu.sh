#!/bin/bash

# 配置变量
REMOTE_USER="root"
REMOTE_HOST="8.210.203.136"
REMOTE_PASS="qq3128537"
REMOTE_PATH="/www/wwwroot/www.jiumiu.com"
LOCAL_PATH="/Users/sxh/coumian/coumian/coumian/web"

# 使用 sshpass 和 rsync 传输文件
sshpass -p "${REMOTE_PASS}" rsync -avz --delete \
    --exclude '.git/' \
    --exclude '.DS_Store' \
    --exclude 'node_modules/' \
    --exclude 'vendor/' \
    "${LOCAL_PATH}/" "${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}/"

# 设置文件权限
sshpass -p "${REMOTE_PASS}" ssh -o StrictHostKeyChecking=no "${REMOTE_USER}@${REMOTE_HOST}" "
    chown -R www:www ${REMOTE_PATH}
    chmod -R 755 ${REMOTE_PATH}
    chmod -R 777 ${REMOTE_PATH}/public/uploads
    cd ${REMOTE_PATH} && composer install --no-dev
"
