import requests
import time
from datetime import datetime
import os
import sys
from typing import List, Dict, Any
import json

class ResourceChecker:
    def __init__(self):
        self.resources = [
            {
                'name': 'Tasks API',
                'url': 'http://localhost:8000/tasks.php',
                'type': 'api'
            },
            {
                'name': 'Notification Receiver',
                'url': 'http://localhost:8000/receiver.html',
                'type': 'page'
            },
            {
                'name': 'Notification Sender',
                'url': 'http://localhost:8000/send_notification.php',
                'type': 'api'
            }
        ]
        self.failed_resources: List[Dict[str, Any]] = []
        self.last_check_time = None

    def check_resource(self, resource: Dict[str, str]) -> bool:
        try:
            response = requests.get(resource['url'], timeout=5)
            if response.status_code == 200:
                if resource['type'] == 'api':
                    # 验证返回的是有效的JSON
                    try:
                        json.loads(response.text)
                    except json.JSONDecodeError:
                        return False
                return True
            return False
        except requests.exceptions.RequestException:
            return False

    def check_all_resources(self):
        self.failed_resources.clear()
        self.last_check_time = datetime.now()
        
        for resource in self.resources:
            if not self.check_resource(resource):
                self.failed_resources.append({
                    **resource,
                    'error': '连接失败或返回非200状态码'
                })

    def display_results(self):
        os.system('clear' if os.name == 'posix' else 'cls')
        print("\n=== 资源检查结果 ===")
        print(f"检查时间: {self.last_check_time.strftime('%Y-%m-%d %H:%M:%S')}")
        print("=" * 50)

        if not self.failed_resources:
            print("\n✅ 所有资源加载正常")
        else:
            print(f"\n❌ 发现 {len(self.failed_resources)} 个资源加载失败:")
            for resource in self.failed_resources:
                print(f"\n资源名称: {resource['name']}")
                print(f"资源类型: {resource['type']}")
                print(f"URL: {resource['url']}")
                print(f"错误: {resource.get('error', '未知错误')}")
                print("-" * 50)

        print("\n按 Ctrl+C 停止检查")

def main():
    checker = ResourceChecker()
    print("开始检查资源加载状态...")
    
    try:
        while True:
            checker.check_all_resources()
            checker.display_results()
            time.sleep(1)  # 每秒检查一次
    except KeyboardInterrupt:
        print("\n检查已停止")

if __name__ == "__main__":
    main()
