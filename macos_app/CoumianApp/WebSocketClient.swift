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
        guard let url = URL(string: "ws://localhost:8081") else { return }
        
        if #available(macOS 10.15, *) {
            let endpoint = NWEndpoint.url(url)
            let parameters = NWParameters.init(tls: nil)
            parameters.defaultProtocolStack.applicationProtocols.insert(NWProtocolWebSocket.Options(), at: 0)
            
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
                    print("WebSocket状态: \(state)")
                    break
                }
            }
            
            connection?.start(queue: .main)
        } else {
            print("系统版本过低，不支持 Network framework")
        }
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
                print("收到消息: \(message)")  // 添加日志
                // 解析JSON消息
                if let jsonData = message.data(using: .utf8),
                   let json = try? JSONSerialization.jsonObject(with: jsonData) as? [String: Any],
                   let type = json["type"] as? String,
                   let title = json["title"] as? String,
                   let content = json["content"] as? String {
                    
                    print("解析JSON: type=\(type), title=\(title), content=\(content)")  // 添加日志
                    
                    if type == "notification" {
                        DispatchQueue.main.async {
                            self?.delegate.showNotificationWindow(title: title, message: content)
                        }
                    }
                } else {
                    print("JSON解析失败")  // 添加日志
                }
            }
            
            if !isComplete {
                self?.receiveMessage()
            }
        }
    }
    
    func send(_ message: String) {
        guard let data = message.data(using: .utf8) else { return }
        
        let metadata = NWProtocolWebSocket.Metadata(opcode: .text)
        let context = NWConnection.ContentContext(identifier: "textContext",
                                               metadata: [metadata])
        
        connection?.send(content: data,
                       contentContext: context,
                       isComplete: true,
                       completion: .contentProcessed { error in
            if let error = error {
                print("发送消息出错: \(error)")
            }
        })
    }
    
    deinit {
        connection?.cancel()
    }
}
