const { app, BrowserWindow, Tray, Menu, ipcMain } = require('electron');
const path = require('path');
const WebSocket = require('ws');

let tray = null;
let notificationWindow = null;
let ws = null;

function createTray() {
    tray = new Tray(path.join(__dirname, 'assets', 'icon.png'));
    const contextMenu = Menu.buildFromTemplate([
        { label: '打开网站', click: () => {
            require('electron').shell.openExternal('http://localhost:8080');
        }},
        { type: 'separator' },
        { label: '退出', click: () => app.quit() }
    ]);
    tray.setToolTip('凑面');
    tray.setContextMenu(contextMenu);
}

function createNotificationWindow(title, content) {
    if (notificationWindow) {
        notificationWindow.close();
    }

    notificationWindow = new BrowserWindow({
        width: 400,
        height: 200,
        frame: false,
        transparent: true,
        resizable: false,
        skipTaskbar: true,
        webPreferences: {
            nodeIntegration: true,
            contextIsolation: false
        }
    });

    notificationWindow.loadFile('notification.html');

    notificationWindow.webContents.on('did-finish-load', () => {
        notificationWindow.webContents.send('notification-data', { title, content });
    });

    // 将窗口放置在屏幕右上角
    const { screen } = require('electron');
    const primaryDisplay = screen.getPrimaryDisplay();
    const { width, height } = primaryDisplay.workAreaSize;
    notificationWindow.setPosition(width - 420, height - 220);

    // 自动关闭
    setTimeout(() => {
        if (notificationWindow) {
            notificationWindow.close();
            notificationWindow = null;
        }
    }, 5000);
}

function setupWebSocket() {
    ws = new WebSocket('ws://localhost:8081');

    ws.on('open', () => {
        console.log('WebSocket连接已建立');
    });

    ws.on('message', (data) => {
        try {
            const message = JSON.parse(data);
            if (message.type === 'notification') {
                createNotificationWindow(message.title, message.content);
            }
        } catch (error) {
            console.error('解析消息失败:', error);
        }
    });

    ws.on('close', () => {
        console.log('WebSocket连接已关闭，3秒后重试...');
        setTimeout(setupWebSocket, 3000);
    });

    ws.on('error', (error) => {
        console.error('WebSocket错误:', error);
    });
}

app.whenReady().then(() => {
    createTray();
    setupWebSocket();
});

app.on('window-all-closed', (e) => {
    e.preventDefault();
});
