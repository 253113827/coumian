# Coumian Windows 客户端

这是凑面任务提醒应用的 Windows 客户端，使用 Electron 构建。

## 开发环境要求

- Node.js 14+
- npm 6+

## 安装依赖

```bash
npm install
```

## 运行开发版本

```bash
npm start
```

## 构建 Windows 安装包

```bash
npm run build
```

构建完成后，可以在 `dist` 目录下找到安装包。

## 功能

- 系统托盘图标
- WebSocket 实时通知
- 自定义通知窗口
- 自动重连机制

## 注意事项

1. 确保 WebSocket 服务器在 `ws://localhost:8081` 运行
2. 确保 Web 服务器在 `http://localhost:8080` 运行
