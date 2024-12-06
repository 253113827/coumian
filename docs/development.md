# 凑面 (Coumian) 开发文档 v1.0

## 版本历史

| 版本   | 日期         | 描述                 | 作者                    |
|--------|--------------|---------------------|------------------------|
| v1.0   | 2023-12-06  | 首个正式版本发布     | 凑面团队 三颗花生 sxh    |

## 项目概述

凑面是一个基于 Web 的任务提醒应用，支持实时通知功能。用户可以设置任务和提醒时间，系统会在指定时间通过 WebSocket 发送通知。

### 版本特性 (v1.0)
- 任务创建和管理
- 实时WebSocket通知
- 声音提醒功能
- 精确到秒的定时提醒
- 任务状态追踪
- 自动重连机制

## 技术栈

### 后端
- PHP 7.4+
- MySQL 数据库
- WebSocket 服务器
- PDO 数据库连接

### 前端
- HTML5
- CSS (Tailwind CSS)
- JavaScript (原生)
- Web Audio API (通知声音)
- WebSocket 客户端

## 系统架构

### 目录结构
```
coumian/
├── docs/               # 文档
│   ├── development.md  # 开发文档
│   └── development.pdf # PDF版文档
├── web/               # Web 应用目录
│   ├── app/           # 应用核心代码
│   │   ├── Database.php
│   │   └── Task.php
│   ├── config/        # 配置文件
│   │   └── config.php
│   └── public/        # 公共访问目录
│       ├── add_task.php
│       ├── tasks.php
│       ├── receiver.html
│       └── sender.html
└── README.md
```

### 核心组件

1. **数据库管理 (Database.php)**
   - 实现单例模式
   - 管理数据库连接
   - 提供 PDO 实例

2. **任务管理 (Task.php)**
   - 任务的 CRUD 操作
   - 任务状态管理
   - 到期任务检查

3. **WebSocket 通信**
   - 实时通知发送
   - 连接状态管理
   - 自动重连机制

## 数据库设计

### tasks 表
```sql
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    notification_time DATETIME NOT NULL,
    status ENUM('pending', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## API 接口

### 任务管理接口

1. **创建任务**
   - 文件：`add_task.php`
   - 方法：POST
   - 参数：
     - title: 任务标题
     - description: 任务描述
     - notification_date: 提醒日期
     - notification_time: 提醒时间（精确到秒）

2. **获取任务列表**
   - 文件：`tasks.php`
   - 方法：GET
   - 返回：所有任务列表，按提醒时间排序

3. **更新任务状态**
   - 文件：`update_task.php`
   - 方法：POST
   - 参数：
     - task_id: 任务ID
     - status: 新状态

### WebSocket 通信

1. **连接建立**
   - URL: `ws://localhost:8081`
   - 自动重连间隔：3秒

2. **消息格式**
```json
{
    "type": "notification",
    "title": "任务标题",
    "content": "任务描述",
    "timestamp": "ISO 8601 时间戳"
}
```

## 前端实现

### 页面说明

1. **任务列表页 (tasks.php)**
   - 显示所有任务
   - 任务状态管理
   - 倒计时显示
   - WebSocket 连接状态

2. **添加任务页 (add_task.php)**
   - 任务信息输入
   - 日期时间选择（精确到秒）
   - 测试通知功能
   - WebSocket 连接状态显示

3. **通知接收页 (receiver.html)**
   - 通知显示
   - 声音控制
   - 连接状态显示
   - 右下角通知弹窗

### 通知系统

1. **视觉通知**
   - 右下角弹出
   - 动画效果
   - 5秒后自动消失
   - 堆叠显示

2. **声音通知**
   - 使用 Web Audio API
   - 可开关控制
   - 440Hz 提示音
   - 音量适中

## 部署说明

### 环境要求
- PHP 7.4+
- MySQL 5.7+
- Web 服务器（Apache/Nginx）
- 支持 WebSocket 的服务器配置

### 安装步骤

1. **数据库设置**
   ```sql
   CREATE DATABASE coumian;
   USE coumian;
   -- 创建 tasks 表
   ```

2. **配置文件**
   - 复制 `config.php.example` 到 `config.php`
   - 设置数据库连接参数

3. **启动服务**
   - 启动 Web 服务器
   - 启动 WebSocket 服务器

## 测试

### 功能测试
1. 任务创建和管理
2. 通知触发和显示
3. WebSocket 连接
4. 声音控制

### 兼容性测试
- 现代浏览器（Chrome、Firefox、Safari）
- 移动设备响应式设计

## 维护和更新

### 日志记录
- 错误日志
- WebSocket 连接日志
- 任务执行日志

### 性能优化
- 数据库索引
- 连接池
- 缓存策略

## 安全考虑

1. **数据安全**
   - SQL 注入防护（使用 PDO 预处理语句）
   - XSS 防护（输出转义）
   - CSRF 防护

2. **WebSocket 安全**
   - 连接验证
   - 消息加密
   - 错误处理

## 已知问题 (v1.0)
1. WebSocket 连接在某些网络环境下可能不稳定
2. 浏览器后台运行时通知可能延迟
3. 移动端响应式布局需要优化

## 未来展望

### 计划功能 (v2.0)
1. 用户认证系统
2. 任务分类管理
3. 重复任务支持
4. 移动端应用
5. 多语言支持

### 技术改进
1. 引入前端框架（Vue/React）
2. API 规范化（RESTful）
3. 单元测试覆盖
4. 容器化部署

## 贡献指南
1. Fork 项目
2. 创建特性分支
3. 提交更改
4. 发起合并请求

## 许可证
MIT License

## 联系方式
项目维护团队：凑面团队
主要开发者：三颗花生 (sxh)
