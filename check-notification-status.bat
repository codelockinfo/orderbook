@echo off
echo ==========================================
echo Notification Status Checker
echo ==========================================
echo.
echo Opening diagnostic page in your browser...
echo.
echo This page will show:
echo   - Tomorrow's orders
echo   - Which reminders have been sent
echo   - Browser notification status
echo.

REM Open the diagnostic page in default browser
start http://localhost/orderbook/diagnose-notifications.php

echo ==========================================
echo Diagnostic page opened!
echo ==========================================
pause

