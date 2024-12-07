import requests
import pandas as pd
import numpy as np
import time
from datetime import datetime, timedelta
from typing import List, Tuple, Dict
from notification_sender import NotificationSender
import mysql.connector

class BTCRSIMonitor:
    def __init__(self):
        self.base_url = "https://www.okx.com"
        self.notification_sender = NotificationSender("http://localhost:8000")
        
        # 只保留1分钟和15分钟周期
        self.periods = {
            "1m": "1m",    # 1分钟
            "15m": "15m"   # 15分钟
        }
        
        # 为不同周期定义RSI计算周期
        self.rsi_periods = {
            "1m": 14,    # 1分钟用14周期
            "15m": 14    # 15分钟用14周期
        }
        
        # 存储上一次的RSI值，用于判断穿越
        self.last_rsi_values = {period: None for period in self.periods.keys()}
        
    def get_kline_data(self, period: str) -> pd.DataFrame:
        """获取K线数据"""
        endpoint = f"{self.base_url}/api/v5/market/candles"
        params = {
            "instId": "BTC-USDT",
            "bar": period,
            "limit": "100"  # 对于4小时周期，确保有足够的数据点
        }
        
        try:
            print(f"请求数据: {params}")
            response = requests.get(endpoint, params=params)
            response.raise_for_status()
            data = response.json()
            
            print(f"API响应: 成功获取数据，条目数: {len(data['data'])}")
            
            if data["code"] == "0":
                # 提取数据并转换为DataFrame
                df = pd.DataFrame(data["data"], columns=[
                    "timestamp", "open", "high", "low", "close", "volume", "volCcy", "volCcyQuote", "confirm"
                ])
                
                # 转换时间戳为datetime
                df["timestamp"] = pd.to_datetime(df["timestamp"].astype(int), unit='ms')
                
                # 确保数据按时间正确排序
                df = df.sort_values("timestamp")
                
                # 转换价格为float类型
                df["close"] = df["close"].astype(float)
                df["open"] = df["open"].astype(float)
                df["high"] = df["high"].astype(float)
                df["low"] = df["low"].astype(float)
                
                # 只保留需要的列
                df = df[["timestamp", "open", "high", "low", "close", "volume"]]
                
                print(f"数据时间范围: {df['timestamp'].min()} 到 {df['timestamp'].max()}")
                return df
            else:
                print(f"API返回错误: {data}")
                return None
                
        except Exception as e:
            print(f"获取K线数据失败: {str(e)}")
            return None
            
    def calculate_rsi(self, data: pd.DataFrame, period_key: str) -> float:
        """计算RSI值"""
        try:
            period = self.rsi_periods[period_key]
            data = data.sort_values('timestamp')
            delta = data["close"].diff()
            gains = delta.where(delta > 0, 0)
            losses = -delta.where(delta < 0, 0)
            avg_gains = gains.rolling(window=period).mean()
            avg_losses = losses.rolling(window=period).mean()
            rs = avg_gains / avg_losses
            rsi = 100 - (100 / (1 + rs))
            final_rsi = round(rsi.iloc[-1], 2)
            return final_rsi
        except Exception as e:
            print(f"计算RSI失败: {str(e)}")
            return None

    def check_crossover(self, period: str, current_rsi: float) -> str:
        """检查RSI是否发生穿越"""
        last_rsi = self.last_rsi_values.get(period)
        if last_rsi is None:
            self.last_rsi_values[period] = current_rsi
            print(f"初始化RSI: {current_rsi}")
            return "初始化"
        
        crossover_status = "持平"
        # 移除1分钟周期的特殊处理，所有周期使用相同的逻辑
        if current_rsi > 80 and last_rsi <= 80:
            crossover_status = "向上穿越80"
        elif current_rsi < 20 and last_rsi >= 20:
            crossover_status = "向下穿越20"
    
        self.last_rsi_values[period] = current_rsi
        return crossover_status
            
    def add_task(self, title: str, description: str) -> bool:
        """直接添加任务到MySQL数据库"""
        try:
            # 当前时间加5秒
            notification_time = datetime.now() + timedelta(seconds=5)
            
            # 连接MySQL数据库
            connection = mysql.connector.connect(
                host='www.coumian.com',
                user='coumian',
                password='qq3128537',
                database='coumian'
            )
            cursor = connection.cursor()
            
            # 插入任务
            insert_query = """
            INSERT INTO tasks (title, description, notification_time, status)
            VALUES (%s, %s, %s, %s)
            """
            data = (
                title,
                description,
                notification_time.strftime("%Y-%m-%d %H:%M:%S"),
                'pending'
            )
            
            cursor.execute(insert_query, data)
            connection.commit()
            
            task_id = cursor.lastrowid
            print(f"任务添加成功: {title}, ID: {task_id}, 通知时间: {notification_time.strftime('%H:%M:%S')}")
            return True
            
        except mysql.connector.Error as err:
            print(f"数据库错误: {err}")
            return False
        except Exception as e:
            print(f"添加任务失败: {str(e)}")
            return False
        finally:
            if 'connection' in locals() and connection.is_connected():
                cursor.close()
                connection.close()

    def analyze_market_condition(self, rsi_value: float, period: str, price: float) -> str:
        """分析市场状况并返回状态描述"""
        if rsi_value > 50:
            condition = "偏强"
            self.add_task(
                f"BTC {period} RSI偏强提醒",
                f"价格: ${price:,.2f}\nRSI: {rsi_value:.2f}\n状态: {condition}"
            )
        elif rsi_value < 20:
            condition = "超卖"
            self.add_task(
                f"BTC {period} RSI超卖警报",
                f"价格: ${price:,.2f}\nRSI: {rsi_value:.2f}\n状态: {condition}"
            )
        elif rsi_value > 80:
            condition = "超买"
        else:
            condition = "正常"
        
        return condition

    def send_notification(self, period: str, price: float, rsi: float, condition: str, crossover: str) -> bool:
        """打印状态到控制台，不再发送HTTP通知"""
        return True  # 始终返回True，因为我们只需要打印状态

    def print_status(self, period: str, price: float, rsi: float, condition: str, 
                    crossover: str, notification_sent: bool):
        """打印状态到控制台"""
        current_time = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        status = (
            f"{current_time} | "
            f"{period:3} | "
            f"${price:<8,.0f} | "
            f"RSI:{rsi:5.1f} | "
            f"{condition:4} | "
            f"{crossover:8} | "
            f"{'已添加' if condition in ['偏强', '超卖'] else '未添加'}"
        )
        print(status)
            
    def monitor(self):
        """监控主函数"""
        last_clear_time = datetime.now().minute
        
        while True:
            try:
                current_minute = datetime.now().minute
                
                # 每分钟清屏一次
                if current_minute != last_clear_time:
                    print("\033[2J\033[H")  # 清屏
                    last_clear_time = current_minute
                
                current_time = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
                print(f"BTC-RSI监控 - {current_time}")
                print("时间                    | 周期 | 价格      | RSI    | 状态 | 趋势     | 通知")
                print("-" * 90)
                
                for period in self.periods.keys():
                    df = self.get_kline_data(self.periods[period])
                    if df is not None:
                        current_price = float(df["close"].iloc[-1])
                        rsi = self.calculate_rsi(df, period)
                        if rsi is not None:
                            condition = self.analyze_market_condition(rsi, period, current_price)
                            crossover = self.check_crossover(period, rsi)
                            notification_sent = self.send_notification(
                                period, current_price, rsi, condition, crossover
                            )
                            self.print_status(
                                period, current_price, rsi, condition, 
                                crossover, notification_sent
                            )
                    time.sleep(0.5)  # API调用间隔
                    
                print("-" * 90)
                time.sleep(0.5)  # 主循环间隔
                    
            except Exception as e:
                print(f"监控过程发生错误: {str(e)}")
                time.sleep(1)
                
if __name__ == "__main__":
    monitor = BTCRSIMonitor()
    monitor.monitor()
