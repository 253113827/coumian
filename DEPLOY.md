# Coumian 项目部署指南 (宝塔面板环境)

## 目录
1. [环境要求](#环境要求)
2. [准备工作](#准备工作)
3. [部署步骤](#部署步骤)
4. [配置说明](#配置说明)
5. [常见问题](#常见问题)

## 环境要求

- PHP 7.4+ 或 8.0+
- MySQL 5.7+ 或 MariaDB 10.2+
- Python 3.8+
- WebSocket 支持
- 宝塔面板 7.9.0+

## 准备工作

1. 确保宝塔面板已安装以下软件：
   - Nginx 1.18+
   - PHP 7.4+ 或 8.0+
   - MySQL 5.7+ 或 MariaDB 10.2+
   - Python项目管理器
   - PM2管理器

2. 在宝塔面板中安装以下 PHP 扩展：
   - fileinfo
   - mysqli
   - PDO
   - curl
   - openssl

3. 在宝塔面板中安装以下 Python 模块：
   - websockets
   - mysql-connector-python

## 部署步骤

### 1. 创建网站

1. 在宝塔面板中创建新网站：
   - 点击 "网站" -> "添加站点"
   - 填写域名（如：coumian.yourdomain.com）
   - 选择 PHP 版本（推荐 7.4+）
   - 创建数据库并记录数据库信息

### 2. 上传项目文件

1. 使用 SFTP 或 Git 将项目文件上传到网站根目录：
   ```bash
   cd /www/wwwroot/your_domain
   git clone https://github.com/your_username/coumian.git .
   ```

2. 设置目录权限：
   ```bash
   chown -R www:www /www/wwwroot/your_domain
   chmod -R 755 /www/wwwroot/your_domain
   chmod -R 777 /www/wwwroot/your_domain/web/public/uploads
   ```

### 3. 配置数据库

1. 导入数据库结构：
   ```bash
   mysql -u your_db_user -p your_db_name < /www/wwwroot/your_domain/web/database/init.sql
   ```

2. 修改数据库配置文件：
   - 编辑 \`web/config/config.php\`
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_db_user');
   define('DB_PASS', 'your_db_password');
   define('DB_NAME', 'your_db_name');
   ```

### 4. 配置 Nginx

1. 修改网站的 Nginx 配置：
   ```nginx
   server {
       listen 80;
       server_name your_domain.com;
       root /www/wwwroot/your_domain/web/public;
       
       location / {
           index index.php index.html;
           try_files $uri $uri/ /index.php?$query_string;
       }
       
       location ~ \.php$ {
           fastcgi_pass unix:/tmp/php-cgi.sock;
           fastcgi_index index.php;
           fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
           include fastcgi_params;
       }
       
       # WebSocket 支持
       location /ws {
           proxy_pass http://localhost:8081;
           proxy_http_version 1.1;
           proxy_set_header Upgrade $http_upgrade;
           proxy_set_header Connection "upgrade";
           proxy_set_header Host $host;
       }
   }
   ```

### 5. 配置 WebSocket 服务

1. 使用 PM2 启动 WebSocket 服务：
   ```bash
   cd /www/wwwroot/your_domain
   pm2 start notification_sender.py --name coumian_ws
   ```

2. 设置开机自启：
   ```bash
   pm2 save
   pm2 startup
   ```

### 6. 配置 Python 环境

1. 创建虚拟环境：
   ```bash
   cd /www/wwwroot/your_domain
   python3 -m venv venv
   source venv/bin/activate
   ```

2. 安装依赖：
   ```bash
   pip install -r requirements.txt
   ```

## 配置说明

### 关键配置文件

1. 数据库配置 (\`web/config/config.php\`):
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_db_user');
   define('DB_PASS', 'your_db_password');
   define('DB_NAME', 'your_db_name');
   ```

2. WebSocket 配置 (\`notification_sender.py\`):
   ```python
   HOST = 'localhost'
   PORT = 8081
   ```

### 目录权限要求

- \`web/public/uploads\`: 777 (可写)
- 其他目录: 755
- 文件: 644

## 常见问题

### 1. WebSocket 连接失败

检查以下几点：
- 确认 PM2 中的 WebSocket 服务是否正常运行
- 检查防火墙是否开放 8081 端口
- 检查 Nginx 配置中的 WebSocket 代理是否正确

### 2. 文件上传失败

检查以下几点：
- \`uploads\` 目录权限是否正确 (777)
- PHP 配置中的上传限制
- 临时文件目录权限

### 3. 数据库连接错误

检查以下几点：
- 数据库配置信息是否正确
- MySQL 服务是否运行
- 数据库用户权限是否正确

## 维护建议

1. 定期备份：
   ```bash
   # 备份数据库
   mysqldump -u user -p database_name > backup.sql
   
   # 备份上传文件
   tar -czf uploads_backup.tar.gz /www/wwwroot/your_domain/web/public/uploads
   ```

2. 日志监控：
   ```bash
   # 查看 WebSocket 服务日志
   pm2 logs coumian_ws
   
   # 查看 Nginx 错误日志
   tail -f /www/wwwlogs/your_domain.error.log
   ```

3. 性能优化：
   - 启用 PHP OPcache
   - 配置适当的 PHP-FPM 进程数
   - 使用 Redis 缓存（如需要）

## 更新维护

1. 代码更新：
   ```bash
   cd /www/wwwroot/your_domain
   git pull origin main
   ```

2. 重启服务：
   ```bash
   # 重启 WebSocket 服务
   pm2 restart coumian_ws
   
   # 重启 PHP-FPM
   bt restart php
   ```

如有任何问题，请参考项目 GitHub 仓库或提交 Issue。
