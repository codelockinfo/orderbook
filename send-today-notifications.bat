@echo off
echo ========================================
echo Sending Today's Order Notifications
echo ========================================
echo.

REM Get PHP path from WAMP
set PHP_PATH=C:\wamp64\bin\php\php8.2.0\php.exe

REM Check if PHP exists
if not exist "%PHP_PATH%" (
    echo PHP not found at %PHP_PATH%
    echo Please update the PHP_PATH variable in this script
    echo.
    pause
    exit /b 1
)

REM Run the notification script
"%PHP_PATH%" "%~dp0cron\send-today-notifications.php"

echo.
echo ========================================
echo Done!
echo ========================================
echo.
pause

