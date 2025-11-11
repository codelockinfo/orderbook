@echo off
echo ==========================================
echo 3x Daily Notification Sender
echo ==========================================
echo.
echo This will send notifications for orders scheduled TOMORROW
echo based on the current time period:
echo.
echo   Morning   (8 AM - 1 PM)  - Reminder #1
echo   Afternoon (1 PM - 7 PM)  - Reminder #2
echo   Evening   (7 PM - 11 PM) - Reminder #3
echo.
echo ==========================================
echo.

REM Change to the script directory
cd /d "%~dp0"

REM Run the PHP script
php cron\send-3x-daily-notifications.php

echo.
echo ==========================================
echo Done!
echo ==========================================
pause

