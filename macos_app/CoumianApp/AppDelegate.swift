import Cocoa
import UserNotifications

class NotificationWindow: NSWindow {
    let titleLabel: NSTextField
    let messageLabel: NSTextField
    let confirmButton: NSButton
    
    init(title: String, message: String) {
        titleLabel = NSTextField(labelWithString: title)
        titleLabel.font = .boldSystemFont(ofSize: 16)
        titleLabel.alignment = .center
        
        messageLabel = NSTextField(labelWithString: message)
        messageLabel.font = .systemFont(ofSize: 14)
        messageLabel.alignment = .left
        messageLabel.cell?.wraps = true
        
        confirmButton = NSButton(title: "确认", target: nil, action: nil)
        confirmButton.bezelStyle = .rounded
        
        super.init(contentRect: NSRect(x: 0, y: 0, width: 400, height: 200),
                  styleMask: [.titled, .closable],
                  backing: .buffered,
                  defer: false)
        
        self.title = "任务提醒"
        self.isReleasedWhenClosed = false
        
        let contentView = NSView(frame: NSRect(x: 0, y: 0, width: 400, height: 200))
        
        titleLabel.frame = NSRect(x: 20, y: 150, width: 360, height: 30)
        messageLabel.frame = NSRect(x: 20, y: 60, width: 360, height: 80)
        confirmButton.frame = NSRect(x: 160, y: 20, width: 80, height: 30)
        
        contentView.addSubview(titleLabel)
        contentView.addSubview(messageLabel)
        contentView.addSubview(confirmButton)
        
        self.contentView = contentView
        self.center()
        
        confirmButton.target = self
        confirmButton.action = #selector(confirmButtonClicked)
    }
    
    @objc func confirmButtonClicked() {
        super.close()
    }
}

class AppDelegate: NSObject, NSApplicationDelegate {
    var statusItem: NSStatusItem!
    var notificationWindows: [NotificationWindow] = []
    private var webSocketClient: WebSocketClient?
    
    func applicationDidFinishLaunching(_ notification: Notification) {
        if #available(macOS 10.14, *) {
            UNUserNotificationCenter.current().requestAuthorization(options: [.alert, .sound]) { granted, error in
                if granted {
                    print("通知权限已授予")
                }
            }
        }
        
        statusItem = NSStatusBar.system.statusItem(withLength: NSStatusItem.variableLength)
        if let button = statusItem.button {
            button.title = "凑面"
        }
        
        setupMenu()
        setupWebSocket()
    }
    
    func setupMenu() {
        let menu = NSMenu()
        menu.addItem(NSMenuItem(title: "打开网站", action: #selector(openWebsite), keyEquivalent: ""))
        menu.addItem(NSMenuItem.separator())
        menu.addItem(NSMenuItem(title: "退出", action: #selector(quit), keyEquivalent: "q"))
        statusItem.menu = menu
    }
    
    @objc func openWebsite() {
        if let url = URL(string: "http://localhost:8080") {
            NSWorkspace.shared.open(url)
        }
    }
    
    @objc func quit() {
        NSApplication.shared.terminate(nil)
    }
    
    func setupWebSocket() {
        webSocketClient = WebSocketClient(delegate: self)
    }
    
    func showNotificationWindow(title: String, message: String) {
        print("显示通知窗口: title=\(title), message=\(message)")  // 添加日志
        DispatchQueue.main.async {
            let window = NotificationWindow(title: title, message: message)
            self.notificationWindows.append(window)
            window.makeKeyAndOrderFront(nil)
            
            // 将窗口移到屏幕右上角
            if let screen = NSScreen.main {
                let screenFrame = screen.visibleFrame
                let windowFrame = window.frame
                let newOrigin = NSPoint(
                    x: screenFrame.maxX - windowFrame.width - 20,
                    y: screenFrame.maxY - windowFrame.height - 20
                )
                window.setFrameOrigin(newOrigin)
            }
        }
    }
}
