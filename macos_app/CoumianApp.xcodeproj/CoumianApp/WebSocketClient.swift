import Foundation
import Network

class WebSocketClient {
    private var connection: NWConnection?
    private var delegate: AppDelegate
    
    init(delegate: AppDelegate) {
        self.delegate = delegate
        setupConnection()
    }
    
    private func setupConnection() {
        let endpoint = NWEndpoint.url(URL(string: "ws://localhost:8080/ws")!)
        let parameters = NWParameters.tls
        parameters.allowLocalEndpointReuse = true
        parameters.allowFastOpen = true
        
        connection = NWConnection(to: endpoint, using: parameters)
        
        connection?.stateUpdateHandler = { [weak self] state in
            switch state {
            case .ready:
                print("WebSocket连接已就绪")
                self?.receiveMessage()
            case .failed(let error):
                print("WebSocket连接失败: \(error)")
                self?.reconnect()
            case .waiting(let error):
                print("WebSocket等待中: \(error)")
            default:
                break
            }
        }
        
        connection?.start(queue: .main)
    }
    
    private func reconnect() {
        DispatchQueue.main.asyncAfter(deadline: .now() + 3.0) { [weak self] in
            print("尝试重新连接WebSocket...")
            self?.setupConnection()
        }
    }
    
    private func receiveMessage() {
        connection?.receive(minimumIncompleteLength: 1, maximumLength: 65536) { [weak self] content, _, isComplete, error in
            if let error = error {
                print("接收消息出错: \(error)")
                return
            }
            
            if let data = content, let message = String(data: data, encoding: .utf8) {
                // 解析JSON消息
                if let jsonData = message.data(using: .utf8),
                   let json = try? JSONSerialization.jsonObject(with: jsonData) as? [String: Any],
                   let title = json["title"] as? String,
                   let content = json["content"] as? String {
                    // 显示通知窗口
                    self?.delegate.showNotificationWindow(title: title, message: content)
                }
            }
            
            if !isComplete {
                self?.receiveMessage()
            }
        }
    }
    
    func send(_ message: String) {
        guard let data = message.data(using: .utf8) else { return }
        
        connection?.send(content: data, completion: .contentProcessed { error in
            if let error = error {
                print("发送消息出错: \(error)")
            }
        })
    }
    
    deinit {
        connection?.cancel()
    }
}
