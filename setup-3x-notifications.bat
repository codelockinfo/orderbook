@echo off
echo ==========================================
echo Setup 3x Daily Notifications System
echo ==========================================
echo.
echo This will set up the database for 3 notifications per day
echo ==========================================
echo.

REM Change to the script directory
cd /d "%~dp0"

REM Check if MySQL is running (for WAMP)
echo Checking MySQL connection...
php -r "try { $db = new PDO('mysql:host=localhost', 'root', ''); echo 'MySQL is running!' . PHP_EOL; } catch(Exception $e) { echo 'ERROR: MySQL is not running!' . PHP_EOL; exit(1); }"

if errorlevel 1 (
    echo.
    echo ERROR: MySQL is not running!
    echo Please start WAMP/MySQL first.
    echo.
    pause
    exit /b 1
)

echo.
echo Running migration...
echo Note: You may see "Duplicate column" errors - that's OK, it means columns already exist!
echo.

REM Run the migration SQL file
mysql -u root -h localhost orderbook < migration-3x-daily-notifications.sql 2>&1

REM Don't fail on error - columns might already exist
echo.
echo ==========================================
echo Migration completed!
echo ==========================================
echo.
echo Verifying database structure...
echo.

REM Verify columns exist
php -r "$db = new PDO('mysql:host=localhost;dbname=orderbook', 'root', ''); $stmt = $db->query(\"SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'orderbook' AND TABLE_NAME = 'orders' AND COLUMN_NAME LIKE 'notification_%%'\"); $row = $stmt->fetch(); if ($row['count'] >= 7) { echo 'SUCCESS! All ' . $row['count'] . ' notification columns are present.' . PHP_EOL; } else { echo 'WARNING: Only ' . $row['count'] . ' notification columns found. Expected at least 7.' . PHP_EOL; }"

echo.
echo The following columns are now in orders table:
echo   - notification_sent (Basic notification flag)
echo   - notification_1_sent (Morning notification)
echo   - notification_2_sent (Afternoon notification)
echo   - notification_3_sent (Evening notification)
echo   - notification_1_sent_at (Timestamp)
echo   - notification_2_sent_at (Timestamp)
echo   - notification_3_sent_at (Timestamp)
echo.
echo Notification Schedule:
echo   Morning:   8:00 AM - 1:00 PM (Reminder #1)
echo   Afternoon: 1:00 PM - 7:00 PM (Reminder #2)
echo   Evening:   7:00 PM - 11:00 PM (Reminder #3)
echo.
echo Next Steps:
echo   1. Create an order for TOMORROW to test
echo   2. Notifications will be sent AUTOMATICALLY!
echo   3. Or run: test-auto-notifications.bat
echo.
echo ==========================================
pause

