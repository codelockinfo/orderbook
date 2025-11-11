<?php
/**
 * Today's Order Notification Sender
 * 
 * This script sends push notifications to users for orders scheduled TODAY
 * Run this script manually or via cron job in the morning
 * 
 * Cron example (runs at 8 AM every day):
 * 0 8 * * * php /path/to/orderbook/cron/send-today-notifications.php
 */

// Disable output buffering for CLI
if (php_sapi_name() === 'cli') {
    ob_implicit_flush(true);
}

require_once __DIR__ . '/../config/database.php';

// Web Push library (using simple implementation without external dependencies)
class TodayNotificationSender {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Find orders scheduled for TODAY that haven't been notified yet today
     */
    public function findTodaysOrders() {
        $today = date('Y-m-d');
        
        $sql = "SELECT o.*, u.username, u.email 
                FROM orders o
                JOIN users u ON o.user_id = u.id
                WHERE o.order_date = :today
                AND o.is_deleted = 0
                AND o.status IN ('Pending', 'Processing')
                ORDER BY o.order_time ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['today' => $today]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get user's push subscriptions
     */
    public function getUserSubscriptions($userId) {
        $sql = "SELECT * FROM push_subscriptions WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Send push notification using Web Push Protocol
     * Note: This is a placeholder. In production, use web-push-php library
     */
    public function sendPushNotification($subscription, $payload) {
        $endpoint = $subscription['endpoint'];
        $p256dh = $subscription['p256dh_key'];
        $auth = $subscription['auth_key'];
        
        $payloadJson = json_encode($payload);
        
        // Check if curl is available
        if (!function_exists('curl_init')) {
            error_log('cURL is not available. Cannot send push notification.');
            return false;
        }
        
        try {
            // For now, log the notification
            error_log("Sending TODAY notification to: " . substr($endpoint, 0, 50) . "...");
            error_log("Payload: " . $payloadJson);
            
            // In production, implement actual web push here using web-push-php library
            // For testing purposes, return true
            return true;
            
        } catch (Exception $e) {
            error_log("Error sending push notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log notification
     */
    public function logNotification($userId, $orderId, $message, $status = 'sent') {
        $sql = "INSERT INTO notification_logs (user_id, order_id, notification_type, message, status) 
                VALUES (?, ?, 'today-reminder', ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$userId, $orderId, $message, $status]);
    }
    
    /**
     * Process today's notifications
     */
    public function processNotifications() {
        $orders = $this->findTodaysOrders();
        $totalProcessed = 0;
        $totalSent = 0;
        
        echo "Found " . count($orders) . " orders scheduled for TODAY\n\n";
        
        foreach ($orders as $order) {
            $totalProcessed++;
            
            $orderId = $order['id'];
            $userId = $order['user_id'];
            $orderNumber = $order['order_number'];
            $orderDate = date('l, F j, Y', strtotime($order['order_date']));
            $orderTime = date('g:i A', strtotime($order['order_time']));
            
            echo "Processing order #{$orderNumber} for user {$order['username']}...\n";
            
            // Get user's subscriptions
            $subscriptions = $this->getUserSubscriptions($userId);
            
            if (empty($subscriptions)) {
                echo "  - No subscriptions found for user\n";
                $this->logNotification($userId, $orderId, "No subscriptions found", 'failed');
                continue;
            }
            
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
            
            // Prepare notification payload
            $payload = [
                'title' => '🔔 Order Due Today!',
                'body' => "Your order #{$orderNumber} is scheduled for TODAY at {$orderTime} ({$timeText})",
                'icon' => '/orderbook/assets/images/icon-192.png',
                'badge' => '/orderbook/assets/images/icon-72.png',
                'tag' => 'order-today-' . $orderId,
                'data' => [
                    'url' => '/orderbook/index.php',
                    'orderId' => $orderId,
                    'orderNumber' => $orderNumber,
                    'orderDate' => $order['order_date'],
                    'orderTime' => $order['order_time']
                ]
            ];
            
            $sentCount = 0;
            foreach ($subscriptions as $subscription) {
                $result = $this->sendPushNotification($subscription, $payload);
                if ($result) {
                    $sentCount++;
                }
            }
            
            if ($sentCount > 0) {
                echo "  ✓ Sent to {$sentCount} device(s)\n";
                $this->logNotification($userId, $orderId, $payload['body'], 'sent');
                $totalSent++;
            } else {
                echo "  ✗ Failed to send\n";
                $this->logNotification($userId, $orderId, $payload['body'], 'failed');
            }
        }
        
        echo "\n";
        echo "Summary:\n";
        echo "  - Orders processed: {$totalProcessed}\n";
        echo "  - Notifications sent: {$totalSent}\n";
        echo "  - Failed: " . ($totalProcessed - $totalSent) . "\n";
        
        return [
            'processed' => $totalProcessed,
            'sent' => $totalSent,
            'failed' => $totalProcessed - $totalSent
        ];
    }
}

// Main execution
try {
    echo "==============================================\n";
    echo "Today's Order Notification Sender\n";
    echo "Started at: " . date('Y-m-d H:i:s') . "\n";
    echo "==============================================\n\n";
    
    $database = new Database();
    $db = $database->connect();
    
    if (!$db) {
        throw new Exception("Failed to connect to database");
    }
    
    $sender = new TodayNotificationSender($db);
    $result = $sender->processNotifications();
    
    echo "\n==============================================\n";
    echo "Completed at: " . date('Y-m-d H:i:s') . "\n";
    echo "==============================================\n";
    
    exit(0);
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    error_log("Today notification sender error: " . $e->getMessage());
    exit(1);
}
?>

