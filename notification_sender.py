import requests
import datetime
import mysql.connector
import time
from typing import Optional, Dict, Any
from dataclasses import dataclass

@dataclass
class NotificationResult:
    success: bool
    status_code: int
    message: str
    response_data: Optional[Dict[str, Any]] = None

class NotificationSender:
    def __init__(self, base_url: str = "http://www.coumian.com:8000"):
        """
        初始化通知发送器
        
        Args:
            base_url: Coumian服务器的基础URL，默认为远程服务器
        """
        self.base_url = base_url.rstrip('/')
        print(f"初始化通知发送器，基础URL: {self.base_url}")
        
    def send_notification(
        self,
        title: str,
        description: str,
        notification_time: Optional[datetime.datetime] = None
    ) -> NotificationResult:
        """
        插入任务到MySQL数据库，并在任务状态变更时发送通知
        """
        if notification_time is None:
            notification_time = datetime.datetime.now()
        
        # 将提醒时间延后3秒
        notification_time = notification_time + datetime.timedelta(seconds=3)
            
        connection = None
        try:
            # 连接到MySQL数据库
            connection = mysql.connector.connect(
                host='www.coumian.com',
                user='coumian',
                password='qq3128537',
                database='coumian'
            )
            cursor = connection.cursor()
            
            # 插入新任务
            insert_query = """
            INSERT INTO tasks (title, description, notification_time, status)
            VALUES (%s, %s, %s, %s)
            """
            data = (title, description, notification_time.strftime("%Y-%m-%d %H:%M:%S"), 'completed')
            cursor.execute(insert_query, data)
            task_id = cursor.lastrowid
            connection.commit()
            
            # 发送HTTP通知
            notification_url = "http://localhost:8000/web/send_notification.php"
            notification_data = {
                "title": title,
                "description": description,
                "task_id": task_id
            }
            
            try:
                response = requests.post(notification_url, json=notification_data)
                if response.status_code == 200:
                    print(f"通知发送成功，任务ID: {task_id}")
                    return NotificationResult(success=True, status_code=200, message="通知发送成功")
                else:
                    print(f"通知发送失败，HTTP状态码: {response.status_code}")
                    return NotificationResult(success=False, status_code=response.status_code, message=f"通知发送失败: HTTP {response.status_code}")
            except Exception as e:
                print(f"发送通知时发生错误: {str(e)}")
                return NotificationResult(success=False, status_code=0, message=f"发送通知时发生错误: {str(e)}")
                
        except mysql.connector.Error as err:
            print(f"数据库错误: {err}")
            return NotificationResult(success=False, status_code=0, message=f"数据库错误: {err}")
        finally:
            if connection and connection.is_connected():
                cursor.close()
                connection.close()

# 测试代码
if __name__ == "__main__":
    # 创建通知发送器实例
    sender = NotificationSender()
    
    # 发送测试通知
    result = sender.send_notification(
        "测试通知",
        "这是一条测试通知，用于验证系统功能。"
    )
    
    print("\n测试结果:")
    print(f"成功: {result.success}")
    print(f"状态码: {result.status_code}")
    print(f"消息: {result.message}")
    if result.response_data:
        print(f"响应数据: {result.response_data}")
