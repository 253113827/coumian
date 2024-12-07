# Coumian - BTC RSI 监控系统

一个实时监控比特币RSI指标的Web应用，当RSI进入超买或超卖区间时发送通知提醒。

## 功能特点

- 实时监控BTC-USDT的RSI指标
- 支持多个时间周期（1分钟、15分钟、30分钟、1小时）
- 可自定义RSI超买和超卖阈值
- WebSocket实时通知系统
- 支持桌面通知和声音提醒

## 安装要求

- PHP 7.4 或更高版本
- Composer
- Web服务器（Apache/Nginx）或PHP内置服务器

## 快速开始

1. 克隆项目
```bash
git clone https://github.com/yourusername/coumian.git
cd coumian
```

2. 安装依赖
```bash
cd web
composer install
```

3. 启动WebSocket服务器
```bash
php websocket_server.php
```

4. 启动Web服务器（使用PHP内置服务器）
```bash
cd public
php -S localhost:8000
```

5. 访问应用
- 打开 http://localhost:8000/btc_monitor.php 查看BTC RSI监控
- 打开 http://localhost:8000/receiver.html 接收通知

## 配置说明

### RSI设置
- 在BTC监控页面可以设置RSI的超买和超卖阈值
- 默认设置：
  - 超卖阈值：30
  - 超买阈值：70

### 通知设置
- 支持桌面通知（需要浏览器授权）
- 可开启/关闭提示音
- 可自定义通知声音

## 目录结构

```
coumian/
├── web/                # Web应用主目录
│   ├── public/        # 公共访问目录
│   │   ├── btc_monitor.php    # BTC监控页面
│   │   └── receiver.html      # 通知接收页面
│   ├── config/        # 配置文件目录
│   ├── vendor/        # Composer依赖
│   └── websocket_server.php   # WebSocket服务器
├── docs/              # 文档
└── README.md          # 项目说明
```

## 开发说明

### WebSocket通知
- WebSocket服务器运行在8081端口
- 通知格式：
```json
{
    "type": "notification",
    "title": "BTC RSI 提醒",
    "content": "BTC 1m 周期 RSI 已进入超卖区间：29.5",
    "timestamp": 1638844800
}
```

### RSI计算
- 使用14周期RSI
- 数据来源：OKX交易所API
- 支持的时间周期：1m、15m、30m、1H

## 更新日志

请查看 [CHANGELOG.md](CHANGELOG.md) 文件了解详细的更新历史。

## 许可证

MIT License - 详见 LICENSE 文件
