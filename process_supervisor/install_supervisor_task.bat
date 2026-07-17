@echo off
rem Administrator wrapper for the hardened PowerShell Scheduled Task installer.
setlocal
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0install_supervisor_task.ps1"
if errorlevel 1 (
    echo ERROR: Supervisor installation failed. Review the preflight output above.
    exit /b 1
)
exit /b 0
