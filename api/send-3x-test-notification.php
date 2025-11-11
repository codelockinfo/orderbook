<?php
/**
 * Manual 3x Notification Tester
 * Tests the 3x daily notification system from the browser
 */
require_once __DIR__ . '/../config/config.php';
requireLogin();

if (!isLoggedIn()) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->connect();
    
    if (!$db) {
        throw new Exception("Failed to connect to database");
    }
    
    $userId = getCurrentUserId();
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    
    // Get the reminder number from request (default to current period)
    $reminderNumber = isset($_GET['reminder']) ? (int)$_GET['reminder'] : null;
    
    // If not specified, auto-detect based on time
    if ($reminderNumber === null) {
        $currentHour = (int)date('H');
        if ($currentHour >= 8 && $currentHour < 13) {
            $reminderNumber = 1; // Morning
        } elseif ($currentHour >= 13 && $currentHour < 19) {
            $reminderNumber = 2; // Afternoon
        } elseif ($currentHour >= 19 && $currentHour < 23) {
            $reminderNumber = 3; // Evening
        } else {
            $reminderNumber = 1; // Default to morning
        }
    }
    
    // Validate reminder number
    if ($reminderNumber < 1 || $reminderNumber > 3) {
        throw new Exception("Invalid reminder number. Must be 1, 2, or 3");
    }
    
    $notificationField = "notification_{$reminderNumber}_sent";
    
    // Find tomorrow's orders for current user that haven't received this reminder yet
    $sql = "SELECT * FROM orders 
            WHERE user_id = :userId 
            AND order_date = :tomorrow
            AND {$notificationField} = 0
            AND is_deleted = 0
            AND status IN ('Pending', 'Processing')
            ORDER BY order_time ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute(['userId' => $userId, 'tomorrow' => $tomorrow]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($orders)) {
        echo json_encode([
            'success' => false,
            'message' => 'No orders scheduled for tomorrow, or all reminders already sent for today',
            'date' => $tomorrow,
            'reminderNumber' => $reminderNumber,
            'hint' => 'Create an order for tomorrow to test the 3x notification system'
        ]);
        exit;
    }
    
    // Get user's push subscriptions
    $stmt = $db->prepare("SELECT * FROM push_subscriptions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($subscriptions)) {
        echo json_encode([
            'success' => false,
            'message' => 'No push subscriptions found. Please enable notifications first.',
            'orders' => count($orders)
        ]);
        exit;
    }
    
    $notifications = [];
    $periodNames = [
        1 => 'Morning',
        2 => 'Afternoon',
        3 => 'Evening'
    ];
    $periodEmojis = [
        1 => '🌅',
        2 => '☀️',
        3 => '🌙'
    ];
    
    $reminderTexts = [
        1 => "Morning reminder: Your order is scheduled for TOMORROW!",
        2 => "Afternoon reminder: Don't forget your order tomorrow!",
        3 => "Evening reminder: Your order is coming up tomorrow!"
    ];
    
    $periodName = $periodNames[$reminderNumber];
    $periodEmoji = $periodEmojis[$reminderNumber];
    $reminderText = $reminderTexts[$reminderNumber];
    
    foreach ($orders as $order) {
        $orderNumber = $order['order_number'];
        $orderDate = date('l, F j, Y', strtotime($order['order_date']));
        $orderTime = date('g:i A', strtotime($order['order_time']));
        
        $notifications[] = [
            'orderNumber' => $orderNumber,
            'orderTime' => $orderTime,
            'orderDate' => $orderDate,
            'reminderNumber' => $reminderNumber,
            'reminderPeriod' => $periodName,
            'subscriptions' => count($subscriptions)
        ];
        
        // Mark notification as sent
        $updateSql = "UPDATE orders 
                      SET {$notificationField} = 1, 
                          notification_{$reminderNumber}_sent_at = NOW() 
                      WHERE id = ?";
        $updateStmt = $db->prepare($updateSql);
        $updateStmt->execute([$order['id']]);
        
        // Log the notification
        $message = "{$periodName} reminder #{$reminderNumber}: Order #{$orderNumber} on {$orderDate} at {$orderTime}";
        $notificationType = ['morning-reminder', 'afternoon-reminder', 'evening-reminder'][$reminderNumber - 1];
        
        $logSql = "INSERT INTO notification_logs 
                   (user_id, order_id, notification_type, reminder_number, message, status) 
                   VALUES (?, ?, ?, ?, ?, 'sent')";
        $logStmt = $db->prepare($logSql);
        $logStmt->execute([$userId, $order['id'], $notificationType, $reminderNumber, $message]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => "{$periodEmoji} {$periodName} notifications (Reminder #{$reminderNumber}) triggered for " . count($orders) . " order(s)",
        'date' => $tomorrow,
        'reminderNumber' => $reminderNumber,
        'reminderPeriod' => $periodName,
        'reminderText' => $reminderText,
        'orders' => $notifications,
        'schedule' => [
            'Morning (8 AM - 1 PM)' => 'Reminder #1',
            'Afternoon (1 PM - 7 PM)' => 'Reminder #2',
            'Evening (7 PM - 11 PM)' => 'Reminder #3'
        ],
        'note' => 'Each order will receive 3 reminders throughout the day before the order date'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>

