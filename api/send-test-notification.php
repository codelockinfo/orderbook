<?php
/**
 * Manual Notification Trigger
 * Run this from browser to send notifications for today's orders
 */
require_once __DIR__ . '/../config/config.php';
requireLogin();

// Only allow logged-in users
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
    $today = date('Y-m-d');
    
    // Find today's orders for current user
    $sql = "SELECT * FROM orders 
            WHERE user_id = :userId 
            AND order_date = :today
            AND is_deleted = 0
            AND status IN ('Pending', 'Processing')
            ORDER BY order_time ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute(['userId' => $userId, 'today' => $today]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($orders)) {
        echo json_encode([
            'success' => false,
            'message' => 'No orders scheduled for today',
            'date' => $today
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
    
    foreach ($orders as $order) {
        $orderNumber = $order['order_number'];
        $orderTime = date('g:i A', strtotime($order['order_time']));
        
        // Calculate time until order
        $orderDateTime = strtotime($order['order_date'] . ' ' . $order['order_time']);
        $now = time();
        $hoursUntil = round(($orderDateTime - $now) / 3600, 1);
        
        $timeText = '';
        if ($hoursUntil <= 0) {
            $timeText = "now";
        } elseif ($hoursUntil < 1) {
            $minutesUntil = round(($orderDateTime - $now) / 60);
            $timeText = "in {$minutesUntil} minutes";
        } elseif ($hoursUntil < 24) {
            $timeText = "in " . round($hoursUntil, 1) . " hours";
        } else {
            $timeText = "today";
        }
        
        $notifications[] = [
            'orderNumber' => $orderNumber,
            'orderTime' => $orderTime,
            'timeUntil' => $timeText,
            'subscriptions' => count($subscriptions)
        ];
        
        // Log the notification
        $message = "Order #{$orderNumber} is scheduled for TODAY at {$orderTime} ({$timeText})";
        $logSql = "INSERT INTO notification_logs (user_id, order_id, notification_type, message, status) 
                   VALUES (?, ?, 'manual-today', ?, 'sent')";
        $logStmt = $db->prepare($logSql);
        $logStmt->execute([$userId, $order['id'], $message]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Notifications triggered for ' . count($orders) . ' order(s)',
        'date' => $today,
        'orders' => $notifications,
        'note' => 'Note: Actual push notifications require web-push-php library. Currently logging only.'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>

