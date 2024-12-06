import requests
import datetime
from typing import Optional, Dict, Any
from dataclasses import dataclass

@dataclass
class NotificationResult:
    success: bool
    status_code: int
    message: str
    response_data: Optional[Dict[str, Any]] = None

class NotificationSender:
    def __init__(self, base_url: str = "http://localhost:8000"):
        """
        初始化通知发送器
        
        Args:
            base_url: Coumian服务器的基础URL，默认为http://localhost:8000
        """
        self.base_url = base_url.rstrip('/')
        
    def send_notification(
        self,
        title: str,
        description: str,
        notification_time: Optional[datetime.datetime] = None
    ) -> NotificationResult:
        """
        发送通知到Coumian系统
        
        Args:
            title: 通知标题
            description: 通知描述
            notification_time: 通知时间，如果不指定则使用当前时间
            
        Returns:
            NotificationResult: 包含发送结果的对象
        """
        if notification_time is None:
            notification_time = datetime.datetime.now()
            
        data = {
            'title': title,
            'description': description,
            'notification_date': notification_time.strftime("%Y-%m-%d"),
            'notification_time': notification_time.strftime("%H:%M:%S")
        }
        
        try:
            response = requests.post(f"{self.base_url}/add_task.php", data=data)
            
            return NotificationResult(
                success=response.status_code == 200,
                status_code=response.status_code,
                message="通知发送成功" if response.status_code == 200 else "通知发送失败",
                response_data={"text": response.text} if response.status_code == 200 else None
            )
            
        except Exception as e:
            return NotificationResult(
                success=False,
                status_code=0,
                message=f"发送失败: {str(e)}"
            )

# 使用示例
if __name__ == "__main__":
    # 创建通知发送器实例
    sender = NotificationSender()
    
    # 发送测试通知
    result = sender.send_notification(
        title="Python测试通知",
        description="这是一条来自NotificationSender的测试通知"
    )
    
    # 打印结果
    print(f"发送状态: {'成功' if result.success else '失败'}")
    print(f"状态码: {result.status_code}")
    print(f"消息: {result.message}")
