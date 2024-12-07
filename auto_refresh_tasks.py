import requests
import time
import json
from datetime import datetime
import os

def clear_screen():
    os.system('clear' if os.name == 'posix' else 'cls')

def format_time(time_str):
    # 将MySQL时间字符串转换为更友好的格式
    dt = datetime.strptime(time_str, '%Y-%m-%d %H:%M:%S')
    return dt.strftime('%Y-%m-%d %H:%M:%S')

def get_tasks():
    try:
        response = requests.get('http://localhost:8000/tasks.php')
        if response.status_code == 200:
            return response.json()
    except Exception as e:
        print(f"Error: {e}")
    return []

def display_tasks(tasks):
    clear_screen()
    print("\n=== 任务列表 ===")
    print(f"上次更新时间: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print("=" * 50)
    
    if not tasks:
        print("\n暂无任务")
        return
        
    for task in tasks:
        status_symbol = "✅" if task['status'] == 'completed' else "⏳"
        print(f"\n{status_symbol} 任务ID: {task['id']}")
        print(f"标题: {task['title']}")
        print(f"描述: {task['description']}")
        print(f"时间: {format_time(task['notification_time'])}")
        print(f"状态: {task['status']}")
        print("-" * 50)

def main():
    print("开始监控任务... 按 Ctrl+C 停止")
    try:
        while True:
            tasks = get_tasks()
            display_tasks(tasks)
            time.sleep(1)  # 每秒刷新一次
    except KeyboardInterrupt:
        print("\n监控已停止")

if __name__ == "__main__":
    main()
