import mysql.connector

def check_table_structure():
    try:
        # 连接到MySQL数据库
        connection = mysql.connector.connect(
            host='www.coumian.com',
            user='coumian',
            password='qq3128537',
            database='coumian'
        )
        cursor = connection.cursor()
        
        # 获取表结构
        cursor.execute("DESCRIBE tasks")
        
        # 打印表结构
        print("\nTable structure for 'tasks':")
        print("Field\t\tType\t\tNull\tKey\tDefault\tExtra")
        print("-" * 80)
        for row in cursor:
            print(f"{row[0]}\t\t{row[1]}\t\t{row[2]}\t{row[3]}\t{row[4]}\t{row[5]}")
            
    except mysql.connector.Error as err:
        print(f"数据库错误: {err}")
    finally:
        if connection.is_connected():
            cursor.close()
            connection.close()

if __name__ == "__main__":
    check_table_structure()
