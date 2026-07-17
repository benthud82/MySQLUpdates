"""Dummy loop script for testing the supervisor and dashboard.
Prints a timestamp every 10 seconds, forever - same shape as the real
jobsched/table_sched loop scripts but with no side effects."""

import time
from datetime import datetime

print("test_loop starting")
while True:
    print("test_loop alive at {}".format(datetime.now().strftime("%Y-%m-%d %H:%M:%S")), flush=True)
    time.sleep(10)
