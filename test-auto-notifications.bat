@echo off
echo ==========================================
echo Test Automatic Notification System
echo ==========================================
echo.
echo This will open your app in the browser.
echo.
echo TO TEST AUTOMATIC NOTIFICATIONS:
echo.
echo 1. Click "Add Order" button
echo 2. Fill in order details:
echo    - Order Number: TEST001
echo    - Date: TOMORROW's date
echo    - Time: Any time you want
echo    - Status: Pending
echo 3. Click Save
echo.
echo EXPECTED RESULT:
echo   - Order created successfully
echo   - Green message appears with notification details
echo   - Browser notification pops up (if enabled)
echo   - Message shows which reminder was sent
echo.
echo DIFFERENT SCENARIOS TO TEST:
echo   A) Order for TOMORROW = Auto notification sent NOW
echo   B) Order for TODAY = "Due today" notification sent NOW
echo   C) Order for FUTURE = "Scheduled" message shown
echo.
echo ==========================================
echo Opening app...
echo ==========================================
echo.

start http://localhost/orderbook/index.php

echo.
echo App opened! Try creating an order now.
echo.
pause

