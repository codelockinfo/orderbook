@echo off
echo ==========================================
echo Setup Windows Task Scheduler for Automatic Notifications
echo ==========================================
echo.
echo This will create 3 scheduled tasks that run automatically:
echo   - Morning (8 AM) - Sends morning reminders
echo   - Afternoon (2 PM) - Sends afternoon reminders
echo   - Evening (8 PM) - Sends evening reminders
echo.
echo These tasks will run EVERY DAY automatically, even if the website is closed!
echo.
echo ==========================================
echo.
pause

REM Change to the script directory
cd /d "%~dp0"

REM Find PHP path
echo Looking for PHP...
set PHP_PATH=C:\wamp64\bin\php\php8.3.14\php.exe

REM Check if PHP exists
if not exist "%PHP_PATH%" (
    echo ERROR: PHP not found at %PHP_PATH%
    echo.
    echo Please update the PHP_PATH in this batch file to match your PHP installation.
    echo Common locations:
    echo   - C:\wamp64\bin\php\php8.3.14\php.exe
    echo   - C:\wamp64\bin\php\php8.2.0\php.exe
    echo   - C:\xampp\php\php.exe
    echo.
    pause
    exit /b 1
)

echo Found PHP at: %PHP_PATH%
echo.

REM Get the full path to the script
set SCRIPT_PATH=%CD%\cron\send-3x-daily-notifications.php

echo Script path: %SCRIPT_PATH%
echo.

REM Create Morning Task (8 AM)
echo Creating Morning Task (8 AM)...
schtasks /create /tn "OrderBook - Morning Notifications" /tr "\"%PHP_PATH%\" \"%SCRIPT_PATH%\"" /sc daily /st 08:00 /f
if errorlevel 1 (
    echo ERROR: Failed to create morning task. You may need to run this as Administrator.
    echo Right-click this file and select "Run as administrator"
    pause
    exit /b 1
)
echo ✓ Morning task created successfully!
echo.

REM Create Afternoon Task (2 PM)
echo Creating Afternoon Task (2 PM)...
schtasks /create /tn "OrderBook - Afternoon Notifications" /tr "\"%PHP_PATH%\" \"%SCRIPT_PATH%\"" /sc daily /st 14:00 /f
echo ✓ Afternoon task created successfully!
echo.

REM Create Evening Task (8 PM)
echo Creating Evening Task (8 PM)...
schtasks /create /tn "OrderBook - Evening Notifications" /tr "\"%PHP_PATH%\" \"%SCRIPT_PATH%\"" /sc daily /st 20:00 /f
echo ✓ Evening task created successfully!
echo.

echo ==========================================
echo SUCCESS! Automated tasks created.
echo ==========================================
echo.
echo The following tasks will now run AUTOMATICALLY every day:
echo.
echo   Task Name                          Time      What It Does
echo   --------------------------------   --------  ------------------------
echo   OrderBook - Morning Notifications  8:00 AM   Sends morning reminders
echo   OrderBook - Afternoon              2:00 PM   Sends afternoon reminders
echo   OrderBook - Evening                8:00 PM   Sends evening reminders
echo.
echo These tasks will:
echo   ✓ Run even if website is closed
echo   ✓ Run even if user is logged out
echo   ✓ Send notifications to all users with orders tomorrow
echo   ✓ Track which reminders have been sent
echo.
echo To view or manage these tasks:
echo   1. Press Win + R
echo   2. Type: taskschd.msc
echo   3. Look for "OrderBook" tasks in the list
echo.
echo To test if it's working:
echo   - Wait until next scheduled time (8 AM, 2 PM, or 8 PM)
echo   - OR manually run: send-3x-notifications.bat
echo.
echo ==========================================
pause

