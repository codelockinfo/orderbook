<?php
/**
 * Reset Notification Flags
 * 
 * This script resets notification flags for orders so they can be tested again
 * Usage: php reset-notification-flags.php [order_number] [reminder_number]
 * 
 * Examples:
 *   php reset-notification-flags.php 0002 1    # Reset reminder #1 for order #0002
 *   php reset-notification-flags.php 0002      # Reset all reminders for order #0002
 *   php reset-notification-flags.php          # Reset all reminders for all orders tomorrow
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

// Ensure timezone is set
if (!ini_get('date.timezone') || ini_get('date.timezone') !== 'Asia/Kolkata') {
    date_default_timezone_set('Asia/Kolkata');
}

try {
    $database = new Database();
    $db = $database->connect();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    $orderNumber = $argv[1] ?? null;
    $reminderNumber = isset($argv[2]) ? (int)$argv[2] : null;
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    
    echo "==============================================\n";
    echo "Reset Notification Flags\n";
    echo "==============================================\n\n";
    echo "Tomorrow's date: {$tomorrow}\n";
    
    if ($orderNumber) {
        echo "Order number: {$orderNumber}\n";
    } else {
        echo "Resetting for ALL orders tomorrow\n";
    }
    
    if ($reminderNumber) {
        echo "Reminder number: {$reminderNumber}\n";
    } else {
        echo "Resetting ALL reminders (1, 2, 3)\n";
    }
    echo "\n";
    
    // Build WHERE clause
    $where = "WHERE order_date = :tomorrow AND is_deleted = 0";
    $params = ['tomorrow' => $tomorrow];
    
    if ($orderNumber) {
        $where .= " AND order_number = :order_number";
        $params['order_number'] = $orderNumber;
    }
    
    // Get orders first to show what will be reset
    $selectSql = "SELECT id, order_number, order_date, 
                         notification_1_sent, notification_2_sent, notification_3_sent
                  FROM orders 
                  {$where}";
    $stmt = $db->prepare($selectSql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($orders)) {
        echo "⚠️  No orders found matching criteria.\n";
        exit(0);
    }
    
    echo "Found " . count($orders) . " order(s):\n";
    foreach ($orders as $order) {
        echo "  - Order #{$order['order_number']} (ID: {$order['id']})\n";
        echo "    Current flags: 1={$order['notification_1_sent']}, 2={$order['notification_2_sent']}, 3={$order['notification_3_sent']}\n";
    }
    echo "\n";
    
    // Build UPDATE SQL
    if ($reminderNumber) {
        $updateSql = "UPDATE orders 
                     SET notification_{$reminderNumber}_sent = 0,
                         notification_{$reminderNumber}_sent_at = NULL
                     {$where}";
    } else {
        $updateSql = "UPDATE orders 
                     SET notification_1_sent = 0,
                         notification_1_sent_at = NULL,
                         notification_2_sent = 0,
                         notification_2_sent_at = NULL,
                         notification_3_sent = 0,
                         notification_3_sent_at = NULL
                     {$where}";
    }
    
    $updateStmt = $db->prepare($updateSql);
    $updateStmt->execute($params);
    $affected = $updateStmt->rowCount();
    
    echo "✅ Reset complete! {$affected} order(s) updated.\n";
    echo "\n";
    echo "You can now test notifications again by running:\n";
    echo "  php send-3x-daily-notifications.php\n";
    echo "  OR\n";
    echo "  Access: cron/auto-trigger.php\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>

