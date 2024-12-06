# 凑面 - 任务提醒应用

凑面是一个简单高效的任务提醒工具，包含Web端和macOS客户端应用。

## 功能特点

- 在Web端添加和管理任务
- 设置任务提醒时间
- macOS系统通知提醒
- 实时WebSocket通信
- 响应式设计，支持手机和桌面访问

## 安装说明

### Web端

1. 安装PHP和MySQL
2. 配置数据库：
   ```bash
   mysql -u root -p < web/database/init.sql
   ```

3. 安装依赖：
   ```bash
   cd web
   composer install
   ```

4. 配置Web服务器（Apache/Nginx）指向`web/public`目录

5. 启动WebSocket服务器：
   ```bash
   php web/app/websocket_server.php
   ```

6. 配置定时任务：
   ```bash
   crontab -e
   # 添加以下内容：
   * * * * * php /path/to/web/app/check_notifications.php
   ```

### macOS客户端

1. 进入macOS应用目录：
   ```bash
   cd macos_app/Coumian
   ```

2. 构建应用：
   ```bash
   swift build
   ```

3. 运行应用：
   ```bash
   .build/debug/Coumian
   ```

## 使用说明

1. 打开网页端（默认地址：http://localhost:8080）
2. 点击"添加任务"创建新任务
3. 设置任务标题、描述和提醒时间
4. 在macOS端运行客户端应用
5. 到达提醒时间时，系统会自动发送通知

## 技术栈

- 后端：PHP、MySQL、WebSocket
- 前端：HTML、TailwindCSS
- macOS客户端：Swift、Cocoa、UserNotifications
- 通信：WebSocket (Ratchet/Starscream)
