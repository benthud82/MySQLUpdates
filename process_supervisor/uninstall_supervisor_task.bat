@echo off
rem Stop All from the dashboard first so managed child processes exit cleanly.
rem This removes both task triggers. See README.md for detached-process cleanup.
schtasks /End    /TN "PyProcessSupervisor_Boot"  2>nul
schtasks /End    /TN "PyProcessSupervisor_Watchdog"  2>nul
schtasks /Delete /F /TN "PyProcessSupervisor_Boot"
schtasks /Delete /F /TN "PyProcessSupervisor_Watchdog"
echo Done.
