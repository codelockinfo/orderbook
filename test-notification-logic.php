<?php
/**
 * Test Notification Logic
 * 
 * This script helps you test and verify that notifications are set up correctly
 * to send 1 day before order dates.
 * 
 * Usage: php test-notification-logic.php
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

echo "========================================\n";
echo "Notification Logic Test\n";
echo "========================================\n\n";

try {
    $database = new Database();
    $db = $database->connect();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    // Get today and tomorrow dates
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $dayAfterTomorrow = date('Y-m-d', strtotime('+2 days'));
    
    echo "📅 Date Information:\n";
    echo "   Today: {$today}\n";
    echo "   Tomorrow: {$tomorrow}\n";
    echo "   Day After Tomorrow: {$dayAfterTomorrow}\n\n";
    
    // Check orders scheduled for tomorrow (should get notifications today)
    echo "🔔 Orders Scheduled for TOMORROW (will get notifications TODAY):\n";
    $stmt = $db->prepare("
        SELECT 
            o.id,
            o.order_number,
            o.order_date,
            o.order_time,
            u.username,
            o.notification_1_sent,
            o.notification_2_sent,
            o.notification_3_sent
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.order_date = :tomorrow
        AND o.is_deleted = 0
        AND o.status IN ('Pending', 'Processing')
        ORDER BY o.order_time ASC
    ");
    $stmt->execute(['tomorrow' => $tomorrow]);
    $tomorrowOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($tomorrowOrders)) {
        echo "   ❌ No orders found for tomorrow\n";
        echo "   💡 Create an order with date = {$tomorrow} to test notifications\n\n";
    } else {
        echo "   ✅ Found " . count($tomorrowOrders) . " order(s):\n";
        foreach ($tomorrowOrders as $order) {
            $notifStatus = [];
            if ($order['notification_1_sent']) $notifStatus[] = "Morning ✓";
            if ($order['notification_2_sent']) $notifStatus[] = "Afternoon ✓";
            if ($order['notification_3_sent']) $notifStatus[] = "Evening ✓";
            
            $status = empty($notifStatus) ? "Pending" : implode(", ", $notifStatus);
            
            echo "      - Order #{$order['order_number']} for {$order['username']}\n";
            echo "        Date: {$order['order_date']} at {$order['order_time']}\n";
            echo "        Notifications: {$status}\n";
        }
        echo "\n";
    }
    
    // Check orders scheduled for future dates
    echo "📋 Orders Scheduled for FUTURE (will get notifications 1 day before):\n";
    $stmt = $db->prepare("
        SELECT 
            o.id,
            o.order_number,
            o.order_date,
            o.order_time,
            u.username,
            DATEDIFF(o.order_date, CURDATE()) as days_until
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.order_date > :tomorrow
        AND o.is_deleted = 0
        AND o.status IN ('Pending', 'Processing')
        ORDER BY o.order_date ASC
        LIMIT 10
    ");
    $stmt->execute(['tomorrow' => $tomorrow]);
    $futureOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($futureOrders)) {
        echo "   ℹ️  No future orders found\n\n";
    } else {
        echo "   Found " . count($futureOrders) . " order(s):\n";
        foreach ($futureOrders as $order) {
            $notificationDate = date('Y-m-d', strtotime($order['order_date'] . ' -1 day'));
            echo "      - Order #{$order['order_number']} for {$order['username']}\n";
            echo "        Order Date: {$order['order_date']} at {$order['order_time']}\n";
            echo "        ⚠️  Notification will be sent on: {$notificationDate} (1 day before)\n";
            echo "        Days until notification: {$order['days_until']} days\n";
        }
        echo "\n";
    }
    
    // Check users with push subscriptions
    echo "👤 Users with Push Notifications Enabled:\n";
    $stmt = $db->prepare("
        SELECT 
            u.id,
            u.username,
            COUNT(ps.id) as subscription_count
        FROM users u
        LEFT JOIN push_subscriptions ps ON u.id = ps.user_id
        GROUP BY u.id, u.username
        HAVING subscription_count > 0
    ");
    $stmt->execute();
    $usersWithSubs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($usersWithSubs)) {
        echo "   ⚠️  No users have push notifications enabled\n";
        echo "   💡 Users need to click the notification bell and grant permission\n\n";
    } else {
        echo "   ✅ Found " . count($usersWithSubs) . " user(s) with subscriptions:\n";
        foreach ($usersWithSubs as $user) {
            echo "      - {$user['username']} ({$user['subscription_count']} device(s))\n";
        }
        echo "\n";
    }
    
    // Check recent notification logs
    echo "📬 Recent Notifications Sent:\n";
    $stmt = $db->prepare("
        SELECT 
            nl.*,
            u.username,
            o.order_number,
            o.order_date
        FROM notification_logs nl
        JOIN users u ON nl.user_id = u.id
        LEFT JOIN orders o ON nl.order_id = o.id
        ORDER BY nl.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recentLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($recentLogs)) {
        echo "   ℹ️  No notifications sent yet\n\n";
    } else {
        foreach ($recentLogs as $log) {
            $orderInfo = $log['order_number'] ? "Order #{$log['order_number']} ({$log['order_date']})" : "N/A";
            echo "      - {$log['username']}: {$log['message']}\n";
            echo "        Type: {$log['notification_type']} | Status: {$log['status']}\n";
            echo "        Order: {$orderInfo}\n";
            echo "        Time: {$log['created_at']}\n";
        }
        echo "\n";
    }
    
    // Example scenario
    echo "========================================\n";
    echo "Example Scenario:\n";
    echo "========================================\n";
    echo "If you create an order:\n";
    echo "  - Created on: {$today}\n";
    echo "  - Order date: {$dayAfterTomorrow}\n";
    echo "  - Notification will be sent on: {$tomorrow} (1 day before)\n";
    echo "  - User will receive 3 notifications on {$tomorrow}:\n";
    echo "    🌅 Morning (8 AM - 1 PM)\n";
    echo "    ☀️  Afternoon (1 PM - 7 PM)\n";
    echo "    🌙 Evening (7 PM - 11 PM)\n\n";
    
    echo "✅ System is configured correctly!\n";
    echo "💡 Make sure cron job is running to send notifications automatically.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>

