import schedule
import os
import time
from datetime import datetime
import pytz

def job():
    now = datetime.now(pytz.timezone('US/Eastern'))
    current_time = now.strftime("%H:%M")
    current_day = now.weekday()  # Monday is 0 and Sunday is 6

    # Check if the current time is between 04:00 and 23:55 and the current day is between Monday and Friday
    if "04:00" <= current_time <= "23:55" and 0 <= current_day <= 4:
        os.system("D:\\xampp\htdocs\\MySQLUpdates\\toterefresh.bat")
        print(f"Success! toterefresh.bat completed at {now.strftime('%Y-%m-%d %H:%M:%S')}")

schedule.every(1).minutes.do(job)

while True:
    schedule.run_pending()
    time.sleep(1)
