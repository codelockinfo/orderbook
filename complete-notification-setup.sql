-- ============================================
-- COMPLETE NOTIFICATION SYSTEM SETUP
-- Import this file directly in phpMyAdmin
-- Safe to run multiple times
-- ============================================

USE orderbook;

-- ============================================
-- 1. ADD COLUMNS TO ORDERS TABLE
-- ============================================

-- Check and add notification_sent
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'orderbook' AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'notification_sent');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE orders ADD COLUMN notification_sent TINYINT(1) DEFAULT 0', 
    'SELECT "notification_sent already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add notification_1_sent
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'orderbook' AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'notification_1_sent');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE orders ADD COLUMN notification_1_sent TINYINT(1) DEFAULT 0', 
    'SELECT "notification_1_sent already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add notification_2_sent
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'orderbook' AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'notification_2_sent');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE orders ADD COLUMN notification_2_sent TINYINT(1) DEFAULT 0', 
    'SELECT "notification_2_sent already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add notification_3_sent
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'orderbook' AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'notification_3_sent');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE orders ADD COLUMN notification_3_sent TINYINT(1) DEFAULT 0', 
    'SELECT "notification_3_sent already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add notification_1_sent_at
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'orderbook' AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'notification_1_sent_at');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE orders ADD COLUMN notification_1_sent_at DATETIME NULL', 
    'SELECT "notification_1_sent_at already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add notification_2_sent_at
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'orderbook' AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'notification_2_sent_at');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE orders ADD COLUMN notification_2_sent_at DATETIME NULL', 
    'SELECT "notification_2_sent_at already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add notification_3_sent_at
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'orderbook' AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'notification_3_sent_at');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE orders ADD COLUMN notification_3_sent_at DATETIME NULL', 
    'SELECT "notification_3_sent_at already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- 2. ADD INDEXES
-- ============================================

-- Drop indexes if they exist, then recreate
SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = 'orderbook' AND TABLE_NAME = 'orders' AND INDEX_NAME = 'idx_notification_sent');
SET @sql = IF(@index_exists > 0, 
    'ALTER TABLE orders DROP INDEX idx_notification_sent', 
    'SELECT "Index idx_notification_sent does not exist"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
ALTER TABLE orders ADD INDEX idx_notification_sent (notification_sent);

SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = 'orderbook' AND TABLE_NAME = 'orders' AND INDEX_NAME = 'idx_notification_1_sent');
SET @sql = IF(@index_exists > 0, 
    'ALTER TABLE orders DROP INDEX idx_notification_1_sent', 
    'SELECT "Index idx_notification_1_sent does not exist"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
ALTER TABLE orders ADD INDEX idx_notification_1_sent (notification_1_sent);

SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = 'orderbook' AND TABLE_NAME = 'orders' AND INDEX_NAME = 'idx_notification_2_sent');
SET @sql = IF(@index_exists > 0, 
    'ALTER TABLE orders DROP INDEX idx_notification_2_sent', 
    'SELECT "Index idx_notification_2_sent does not exist"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
ALTER TABLE orders ADD INDEX idx_notification_2_sent (notification_2_sent);

SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = 'orderbook' AND TABLE_NAME = 'orders' AND INDEX_NAME = 'idx_notification_3_sent');
SET @sql = IF(@index_exists > 0, 
    'ALTER TABLE orders DROP INDEX idx_notification_3_sent', 
    'SELECT "Index idx_notification_3_sent does not exist"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
ALTER TABLE orders ADD INDEX idx_notification_3_sent (notification_3_sent);

-- ============================================
-- 3. UPDATE NOTIFICATION_LOGS TABLE
-- ============================================

-- Update enum to include new notification types
ALTER TABLE notification_logs 
MODIFY COLUMN notification_type ENUM(
    'reminder', 
    'status_change', 
    'today-reminder', 
    'manual-today',
    'morning-reminder',
    'afternoon-reminder',
    'evening-reminder'
) DEFAULT 'reminder';

-- Check and add reminder_number column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'orderbook' AND TABLE_NAME = 'notification_logs' AND COLUMN_NAME = 'reminder_number');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE notification_logs ADD COLUMN reminder_number TINYINT(1) NULL AFTER notification_type', 
    'SELECT "reminder_number already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- 4. VERIFICATION
-- ============================================

SELECT '============================================' AS '';
SELECT '✅ MIGRATION COMPLETED SUCCESSFULLY!' AS 'Status';
SELECT '============================================' AS '';

-- Count notification columns
SELECT 
    COUNT(*) as total_columns,
    'notification columns in orders table' as description
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'orderbook'
AND TABLE_NAME = 'orders'
AND COLUMN_NAME LIKE 'notification%';

-- Show all notification columns
SELECT '--------------------------------------------' AS '';
SELECT 'NOTIFICATION COLUMNS IN ORDERS TABLE:' AS '';
SELECT '--------------------------------------------' AS '';

SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    COLUMN_DEFAULT,
    IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'orderbook'
AND TABLE_NAME = 'orders'
AND COLUMN_NAME LIKE 'notification%'
ORDER BY ORDINAL_POSITION;

-- Show notification_logs columns
SELECT '--------------------------------------------' AS '';
SELECT 'NOTIFICATION_LOGS TABLE COLUMNS:' AS '';
SELECT '--------------------------------------------' AS '';

SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'orderbook'
AND TABLE_NAME = 'notification_logs'
AND (COLUMN_NAME = 'notification_type' OR COLUMN_NAME = 'reminder_number')
ORDER BY ORDINAL_POSITION;

SELECT '============================================' AS '';
SELECT '🎉 YOUR DATABASE IS NOW READY!' AS 'Result';
SELECT 'Test by creating an order for TOMORROW' AS 'Next Step';
SELECT '============================================' AS '';

