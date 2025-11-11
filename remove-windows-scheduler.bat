@echo off
echo ==========================================
echo Remove Automated Notification Tasks
echo ==========================================
echo.
echo This will REMOVE the 3 scheduled tasks:
echo   - OrderBook - Morning Notifications
echo   - OrderBook - Afternoon Notifications
echo   - OrderBook - Evening Notifications
echo.
pause

echo.
echo Removing tasks...
echo.

schtasks /delete /tn "OrderBook - Morning Notifications" /f
schtasks /delete /tn "OrderBook - Afternoon Notifications" /f
schtasks /delete /tn "OrderBook - Evening Notifications" /f

echo.
echo ==========================================
echo All automated tasks removed.
echo ==========================================
echo.
pause

