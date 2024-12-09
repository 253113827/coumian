#!/bin/bash

# 删除旧的主机密钥
sed -i.bak '/8.210.203.136/d' ~/.ssh/known_hosts

# 添加新的主机密钥（自动接受新的主机密钥）
sshpass -p "qQ121676463" ssh -o StrictHostKeyChecking=no root@8.210.203.136 "echo 'Connection test successful'"
