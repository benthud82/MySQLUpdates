@echo off
rem Runs the supervisor in a visible console for debugging.
rem Ctrl+C stops the supervisor AND all managed children cleanly.
cd /d "%~dp0"
python process_supervisor.py
pause
