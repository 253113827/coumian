import Cocoa
import UserNotifications
import Starscream

@main
class AppDelegate: NSObject, NSApplicationDelegate, WebSocketDelegate {
    var statusItem: NSStatusItem!
    var webSocket: WebSocket!
    
    func applicationDidFinishLaunching(_ notification: Notification) {
        // 请求通知权限
        if #available(macOS 10.14, *) {
            UNUserNotificationCenter.current().requestAuthorization(options: [.alert, .sound]) { granted, error in
                if granted {
                    print("通知权限已授予")
                }
            }
        }
        
        // 创建状态栏图标
        statusItem = NSStatusBar.system.statusItem(withLength: NSStatusItem.variableLength)
        if let button = statusItem.button {
            button.title = "凑面"
        }
        
        setupMenu()
        connectWebSocket()
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
    
    func connectWebSocket() {
        var request = URLRequest(url: URL(string: "ws://localhost:8080")!)
        request.timeoutInterval = 5
        webSocket = WebSocket(request: request)
        webSocket.delegate = self
        webSocket.connect()
    }
    
    // WebSocket代理方法
    func didReceive(event: WebSocketEvent, client: WebSocketClient) {
        switch event {
        case .connected(let headers):
            print("WebSocket已连接: \(headers)")
        case .disconnected(let reason, let code):
            print("WebSocket已断开连接: \(reason) with code: \(code)")
            // 尝试重新连接
            DispatchQueue.main.asyncAfter(deadline: .now() + 5.0) {
                self.webSocket.connect()
            }
        case .text(let string):
            if let data = string.data(using: .utf8),
               let json = try? JSONSerialization.jsonObject(with: data) as? [String: Any],
               let title = json["title"] as? String,
               let description = json["description"] as? String {
                showNotification(title: title, body: description)
            }
        case .error(let error):
            print("WebSocket错误: \(error?.localizedDescription ?? "Unknown error")")
        default:
            break
        }
    }
    
    func showNotification(title: String, body: String) {
        if #available(macOS 10.14, *) {
            let content = UNMutableNotificationContent()
            content.title = title
            content.body = body
            content.sound = .default
            
            let request = UNNotificationRequest(identifier: UUID().uuidString,
                                              content: content,
                                              trigger: nil)
            
            UNUserNotificationCenter.current().add(request) { error in
                if let error = error {
                    print("显示通知出错: \(error)")
                }
            }
        }
    }
}

_ = NSApplicationMain(CommandLine.argc, CommandLine.unsafeArgv)
