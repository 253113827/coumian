from notification_sender import NotificationSender
import datetime

def test_notification():
    # 创建通知发送器
    sender = NotificationSender()
    
    # 发送当前时间的通知
    result = sender.send_notification(
        title="Python测试通知",
        description="这是一条测试通知",
    )
    
    # 发送未来时间的通知
    future_time = datetime.datetime.now() + datetime.timedelta(minutes=5)
    result_future = sender.send_notification(
        title="未来通知测试",
        description="这是一条5分钟后的通知",
        notification_time=future_time
    )
    
    # 打印结果
    print("=== 当前时间通知 ===")
    print(f"发送状态: {'成功' if result.success else '失败'}")
    print(f"状态码: {result.status_code}")
    print(f"消息: {result.message}")
    
    print("\n=== 未来时间通知 ===")
    print(f"发送状态: {'成功' if result_future.success else '失败'}")
    print(f"状态码: {result_future.status_code}")
    print(f"消息: {result_future.message}")

if __name__ == "__main__":
    test_notification()
