<?php
/**
 * Database Structure Verification Script
 * 
 * This script checks if your database has all the required columns
 * for the notification system to work correctly.
 * 
 * Usage: php check-database-structure.php
 * Or access via browser: http://yoursite.com/check-database-structure.php
 */

// Allow browser access
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/html; charset=utf-8');
    echo '<pre>';
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

echo "========================================\n";
echo "Database Structure Verification\n";
echo "========================================\n\n";

try {
    $database = new Database();
    $db = $database->connect();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    $errors = [];
    $warnings = [];
    $success = [];
    
    // ============================================
    // 1. CHECK ORDERS TABLE
    // ============================================
    echo "📋 Checking ORDERS table...\n";
    echo "----------------------------------------\n";
    
    $requiredColumns = [
        'id' => 'INT',
        'order_number' => 'VARCHAR',
        'order_date' => 'DATE',
        'order_time' => 'TIME',
        'status' => 'ENUM',
        'user_id' => 'INT',
        'is_deleted' => 'TINYINT',
        'created_at' => 'TIMESTAMP',
        'updated_at' => 'TIMESTAMP'
    ];
    
    // Notification columns (required for 1-day-before notifications)
    $notificationColumns = [
        'notification_1_sent' => 'TINYINT(1) DEFAULT 0',
        'notification_2_sent' => 'TINYINT(1) DEFAULT 0',
        'notification_3_sent' => 'TINYINT(1) DEFAULT 0',
        'notification_1_sent_at' => 'DATETIME NULL',
        'notification_2_sent_at' => 'DATETIME NULL',
        'notification_3_sent_at' => 'DATETIME NULL'
    ];
    
    // Get existing columns
    $stmt = $db->query("SHOW COLUMNS FROM orders");
    $existingColumns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingColumns[$row['Field']] = $row;
    }
    
    // Check required columns
    foreach ($requiredColumns as $col => $type) {
        if (isset($existingColumns[$col])) {
            $success[] = "✅ Column '{$col}' exists";
        } else {
            $errors[] = "❌ Missing required column: '{$col}'";
        }
    }
    
    // Check notification columns
    $missingNotificationColumns = [];
    foreach ($notificationColumns as $col => $expected) {
        if (isset($existingColumns[$col])) {
            $success[] = "✅ Notification column '{$col}' exists";
        } else {
            $missingNotificationColumns[] = $col;
            $errors[] = "❌ Missing notification column: '{$col}'";
        }
    }
    
    // Check for group_id (optional but recommended)
    if (isset($existingColumns['group_id'])) {
        $success[] = "✅ Column 'group_id' exists (for group orders)";
    } else {
        $warnings[] = "⚠️  Column 'group_id' not found (optional for group orders)";
    }
    
    echo "\n";
    
    // ============================================
    // 2. CHECK NOTIFICATION_LOGS TABLE
    // ============================================
    echo "📬 Checking NOTIFICATION_LOGS table...\n";
    echo "----------------------------------------\n";
    
    $requiredLogColumns = [
        'id' => 'INT',
        'user_id' => 'INT',
        'order_id' => 'INT',
        'notification_type' => 'ENUM',
        'message' => 'TEXT',
        'status' => 'VARCHAR'
    ];
    
    $optionalLogColumns = [
        'created_at' => 'TIMESTAMP',
        'updated_at' => 'TIMESTAMP'
    ];
    
    // Check if table exists
    $stmt = $db->query("SHOW TABLES LIKE 'notification_logs'");
    if ($stmt->rowCount() > 0) {
        $success[] = "✅ Table 'notification_logs' exists";
        
        // Get columns
        $stmt = $db->query("SHOW COLUMNS FROM notification_logs");
        $logColumns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $logColumns[$row['Field']] = $row;
        }
        
        // Check required columns
        foreach ($requiredLogColumns as $col => $type) {
            if (isset($logColumns[$col])) {
                $success[] = "✅ Log column '{$col}' exists";
            } else {
                $errors[] = "❌ Missing log column: '{$col}'";
            }
        }
        
        // Check optional columns
        foreach ($optionalLogColumns as $col => $type) {
            if (isset($logColumns[$col])) {
                $success[] = "✅ Log column '{$col}' exists";
            } else {
                $warnings[] = "⚠️  Log column '{$col}' not found (recommended for timestamps)";
            }
        }
        
        // Check for reminder_number (optional but recommended)
        if (isset($logColumns['reminder_number'])) {
            $success[] = "✅ Log column 'reminder_number' exists";
        } else {
            $warnings[] = "⚠️  Log column 'reminder_number' not found (optional for tracking reminder numbers)";
        }
    } else {
        $errors[] = "❌ Table 'notification_logs' does not exist";
    }
    
    echo "\n";
    
    // ============================================
    // 3. CHECK PUSH_SUBSCRIPTIONS TABLE
    // ============================================
    echo "🔔 Checking PUSH_SUBSCRIPTIONS table...\n";
    echo "----------------------------------------\n";
    
    $requiredSubColumns = [
        'id' => 'INT',
        'user_id' => 'INT',
        'endpoint' => 'TEXT',
        'p256dh_key' => 'TEXT',
        'auth_key' => 'TEXT',
        'created_at' => 'TIMESTAMP'
    ];
    
    // Check if table exists
    $stmt = $db->query("SHOW TABLES LIKE 'push_subscriptions'");
    if ($stmt->rowCount() > 0) {
        $success[] = "✅ Table 'push_subscriptions' exists";
        
        // Get columns
        $stmt = $db->query("SHOW COLUMNS FROM push_subscriptions");
        $subColumns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $subColumns[$row['Field']] = $row;
        }
        
        // Check required columns
        foreach ($requiredSubColumns as $col => $type) {
            if (isset($subColumns[$col])) {
                $success[] = "✅ Subscription column '{$col}' exists";
            } else {
                $errors[] = "❌ Missing subscription column: '{$col}'";
            }
        }
    } else {
        $errors[] = "❌ Table 'push_subscriptions' does not exist";
    }
    
    echo "\n";
    
    // ============================================
    // 4. CHECK USERS TABLE
    // ============================================
    echo "👤 Checking USERS table...\n";
    echo "----------------------------------------\n";
    
    $requiredUserColumns = ['id', 'username', 'email', 'password'];
    
    $stmt = $db->query("SHOW COLUMNS FROM users");
    $userColumns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $userColumns[$row['Field']] = $row;
    }
    
    foreach ($requiredUserColumns as $col) {
        if (isset($userColumns[$col])) {
            $success[] = "✅ User column '{$col}' exists";
        } else {
            $errors[] = "❌ Missing user column: '{$col}'";
        }
    }
    
    echo "\n";
    
    // ============================================
    // 5. SUMMARY
    // ============================================
    echo "========================================\n";
    echo "VERIFICATION SUMMARY\n";
    echo "========================================\n\n";
    
    if (!empty($success)) {
        echo "✅ SUCCESS (" . count($success) . " checks passed):\n";
        foreach ($success as $msg) {
            echo "   {$msg}\n";
        }
        echo "\n";
    }
    
    if (!empty($warnings)) {
        echo "⚠️  WARNINGS (" . count($warnings) . "):\n";
        foreach ($warnings as $msg) {
            echo "   {$msg}\n";
        }
        echo "\n";
    }
    
    if (!empty($errors)) {
        echo "❌ ERRORS (" . count($errors) . "):\n";
        foreach ($errors as $msg) {
            echo "   {$msg}\n";
        }
        echo "\n";
        
        echo "========================================\n";
        echo "FIX REQUIRED\n";
        echo "========================================\n\n";
        
        if (!empty($missingNotificationColumns)) {
            echo "⚠️  Missing notification columns in ORDERS table!\n";
            echo "   Run this SQL file to fix: complete-notification-setup.sql\n\n";
            echo "   Or run these SQL commands:\n";
            foreach ($missingNotificationColumns as $col) {
                $default = strpos($col, '_sent_at') !== false ? 'DATETIME NULL' : 'TINYINT(1) DEFAULT 0';
                echo "   ALTER TABLE orders ADD COLUMN {$col} {$default};\n";
            }
            echo "\n";
        }
    } else {
        echo "✅ All required database structures are correct!\n";
        echo "   Your database is ready for the notification system.\n\n";
    }
    
    // ============================================
    // 6. DATA CHECK
    // ============================================
    echo "========================================\n";
    echo "DATA CHECK\n";
    echo "========================================\n\n";
    
    // Check orders count
    $stmt = $db->query("SELECT COUNT(*) as count FROM orders WHERE is_deleted = 0");
    $orderCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "📦 Orders: {$orderCount}\n";
    
    // Check subscriptions count
    $stmt = $db->query("SELECT COUNT(*) as count FROM push_subscriptions");
    $subCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "🔔 Push Subscriptions: {$subCount}\n";
    
    // Check notification logs count
    $stmt = $db->query("SELECT COUNT(*) as count FROM notification_logs");
    $logCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "📬 Notification Logs: {$logCount}\n";
    
    // Check orders scheduled for tomorrow
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM orders WHERE order_date = ? AND is_deleted = 0 AND status IN ('Pending', 'Processing')");
    $stmt->execute([$tomorrow]);
    $tomorrowCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "📅 Orders scheduled for tomorrow ({$tomorrow}): {$tomorrowCount}\n";
    
    echo "\n";
    echo "========================================\n";
    echo "Verification Complete!\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Close HTML if browser access
if (php_sapi_name() !== 'cli') {
    echo '</pre>';
}
?>

