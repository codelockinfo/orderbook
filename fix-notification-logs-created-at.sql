-- Fix missing created_at column in notification_logs table
-- Run this in phpMyAdmin

USE orderbook;

-- Check if created_at column exists, if not add it
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'orderbook' 
    AND TABLE_NAME = 'notification_logs' 
    AND COLUMN_NAME = 'created_at');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE notification_logs ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER status', 
    'SELECT "created_at column already exists" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verify the column was added
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    COLUMN_DEFAULT,
    IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'orderbook'
AND TABLE_NAME = 'notification_logs'
AND COLUMN_NAME = 'created_at';

SELECT '✅ Fix completed! The created_at column has been added to notification_logs table.' AS 'Status';

