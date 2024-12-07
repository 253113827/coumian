import mysql.connector

def check_table_structure():
    connection = mysql.connector.connect(
        host='www.coumian.com',
        user='coumian',
        password='qq3128537',
        database='coumian'
    )
    
    cursor = connection.cursor()
    
    # 获取表结构
    cursor.execute("DESCRIBE tasks")
    columns = cursor.fetchall()
    
    print("=== tasks 表结构 ===")
    for column in columns:
        print(f"字段名: {column[0]}")
        print(f"类型: {column[1]}")
        print(f"是否可空: {column[2]}")
        print(f"键类型: {column[3]}")
        print(f"默认值: {column[4]}")
        print("-" * 30)
    
    cursor.close()
    connection.close()

if __name__ == "__main__":
    check_table_structure()
