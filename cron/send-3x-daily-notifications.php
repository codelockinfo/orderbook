<?php
/**
 * 3x Daily Notification Sender
 * 
 * Sends 3 notifications per day (morning, afternoon, evening) for orders scheduled TOMORROW
 * This ensures users get reminded 3 times throughout the day before their order
 * 
 * Schedule Times:
 * - Morning: 8:00 AM (Notification #1)
 * - Afternoon: 2:00 PM (Notification #2)
 * - Evening: 8:00 PM (Notification #3)
 * 
 * Cron Configuration:
 * 0 8 * * * php /path/to/orderbook/cron/send-3x-daily-notifications.php   # Morning (8 AM)
 * 0 14 * * * php /path/to/orderbook/cron/send-3x-daily-notifications.php  # Afternoon (2 PM)
 * 0 20 * * * php /path/to/orderbook/cron/send-3x-daily-notifications.php  # Evening (8 PM)
 * 
 * OR run every hour and it will auto-detect which notification to send:
 * 0 * * * * php /path/to/orderbook/cron/send-3x-daily-notifications.php
 */

// Disable output buffering for CLI
if (php_sapi_name() === 'cli') {
    ob_implicit_flush(true);
}

require_once __DIR__ . '/../config/database.php';

class ThreeTimesNotificationSender {
    private $db;
    
    // Notification time windows (24-hour format)
    const MORNING_START = 8;    // 8:00 AM
    const MORNING_END = 13;      // 1:00 PM
    
    const AFTERNOON_START = 13;  // 1:00 PM
    const AFTERNOON_END = 19;    // 7:00 PM
    
    const EVENING_START = 19;    // 7:00 PM
    const EVENING_END = 23;      // 11:00 PM
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Determine which notification period we're in based on current time
     */
    public function getCurrentNotificationPeriod() {
        $currentHour = (int)date('H');
        
        if ($currentHour >= self::MORNING_START && $currentHour < self::MORNING_END) {
            return 1; // Morning
        } elseif ($currentHour >= self::AFTERNOON_START && $currentHour < self::AFTERNOON_END) {
            return 2; // Afternoon
        } elseif ($currentHour >= self::EVENING_START && $currentHour < self::EVENING_END) {
            return 3; // Evening
        } else {
            return 0; // Outside notification windows
        }
    }
    
    /**
     * Get period name for logging
     */
    public function getPeriodName($period) {
        $names = [
            1 => 'Morning (8 AM - 1 PM)',
            2 => 'Afternoon (1 PM - 7 PM)',
            3 => 'Evening (7 PM - 11 PM)'
        ];
        return $names[$period] ?? 'Unknown';
    }
    
    /**
     * Get notification type for logging
     */
    public function getNotificationType($period) {
        $types = [
            1 => 'morning-reminder',
            2 => 'afternoon-reminder',
            3 => 'evening-reminder'
        ];
        return $types[$period] ?? 'reminder';
    }
    
    /**
     * Find orders needing notification for the current period
     */
    public function findOrdersNeedingNotification($period) {
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        
        // Build SQL based on which notification period
        $notificationField = "notification_{$period}_sent";
        
        $sql = "SELECT o.*, u.username, u.email 
                FROM orders o
                JOIN users u ON o.user_id = u.id
                WHERE o.order_date = :tomorrow
                AND o.{$notificationField} = 0
                AND o.is_deleted = 0
                AND o.status IN ('Pending', 'Processing')
                ORDER BY o.order_time ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['tomorrow' => $tomorrow]);
        
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
     * Send push notification
     */
    public function sendPushNotification($subscription, $payload) {
        $endpoint = $subscription['endpoint'];
        
        $payloadJson = json_encode($payload);
        
        if (!function_exists('curl_init')) {
            error_log('cURL is not available. Cannot send push notification.');
            return false;
        }
        
        try {
            error_log("Sending notification to: " . substr($endpoint, 0, 50) . "...");
            error_log("Payload: " . $payloadJson);
            
            // In production, use web-push-php library here
            return true;
            
        } catch (Exception $e) {
            error_log("Error sending push notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark notification as sent
     */
    public function markNotificationSent($orderId, $period) {
        $notificationField = "notification_{$period}_sent";
        $timestampField = "notification_{$period}_sent_at";
        
        $sql = "UPDATE orders 
                SET {$notificationField} = 1, 
                    {$timestampField} = NOW() 
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$orderId]);
    }
    
    /**
     * Log notification
     */
    public function logNotification($userId, $orderId, $message, $period, $status = 'sent') {
        $notificationType = $this->getNotificationType($period);
        
        $sql = "INSERT INTO notification_logs 
                (user_id, order_id, notification_type, reminder_number, message, status) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $userId, 
            $orderId, 
            $notificationType, 
            $period, 
            $message, 
            $status
        ]);
    }
    
    /**
     * Get emoji for period
     */
    public function getPeriodEmoji($period) {
        $emojis = [
            1 => '🌅', // Morning
            2 => '☀️', // Afternoon
            3 => '🌙'  // Evening
        ];
        return $emojis[$period] ?? '📅';
    }
    
    /**
     * Process notifications for current period
     */
    public function processNotifications() {
        $period = $this->getCurrentNotificationPeriod();
        
        if ($period === 0) {
            echo "⏰ Current time is outside notification windows\n";
            echo "Notification windows:\n";
            echo "  - Morning: 8:00 AM - 1:00 PM\n";
            echo "  - Afternoon: 1:00 PM - 7:00 PM\n";
            echo "  - Evening: 7:00 PM - 11:00 PM\n";
            return [
                'processed' => 0,
                'sent' => 0,
                'failed' => 0,
                'period' => 'none'
            ];
        }
        
        $periodName = $this->getPeriodName($period);
        $periodEmoji = $this->getPeriodEmoji($period);
        
        echo "{$periodEmoji} Processing {$periodName} notifications (Reminder #{$period})\n\n";
        
        $orders = $this->findOrdersNeedingNotification($period);
        $totalProcessed = 0;
        $totalSent = 0;
        
        echo "Found " . count($orders) . " orders needing notification #{$period}\n\n";
        
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
                $this->logNotification($userId, $orderId, "No subscriptions found", $period, 'failed');
                continue;
            }
            
            // Create period-specific message
            $reminderText = [
                1 => "Morning reminder: Your order is scheduled for TOMORROW!",
                2 => "Afternoon reminder: Don't forget your order tomorrow!",
                3 => "Evening reminder: Your order is coming up tomorrow!"
            ];
            
            // Prepare notification payload
            $payload = [
                'title' => "{$periodEmoji} Order Reminder #{$period}",
                'body' => "{$reminderText[$period]} Order #{$orderNumber} on {$orderDate} at {$orderTime}",
                'tag' => "order-reminder-{$period}-{$orderId}",
                'data' => [
                    'url' => '/orderbook/index.php',
                    'orderId' => $orderId,
                    'orderNumber' => $orderNumber,
                    'orderDate' => $order['order_date'],
                    'orderTime' => $order['order_time'],
                    'reminderNumber' => $period
                ],
                'requireInteraction' => false,
                'vibrate' => [200, 100, 200]
            ];
            
            $sentCount = 0;
            foreach ($subscriptions as $subscription) {
                $result = $this->sendPushNotification($subscription, $payload);
                if ($result) {
                    $sentCount++;
                }
            }
            
            if ($sentCount > 0) {
                echo "  ✓ Sent reminder #{$period} to {$sentCount} device(s)\n";
                $this->markNotificationSent($orderId, $period);
                $this->logNotification($userId, $orderId, $payload['body'], $period, 'sent');
                $totalSent++;
            } else {
                echo "  ✗ Failed to send\n";
                $this->logNotification($userId, $orderId, $payload['body'], $period, 'failed');
            }
        }
        
        echo "\n";
        echo "Summary for {$periodName}:\n";
        echo "  - Orders processed: {$totalProcessed}\n";
        echo "  - Notifications sent: {$totalSent}\n";
        echo "  - Failed: " . ($totalProcessed - $totalSent) . "\n";
        
        return [
            'processed' => $totalProcessed,
            'sent' => $totalSent,
            'failed' => $totalProcessed - $totalSent,
            'period' => $periodName,
            'reminderNumber' => $period
        ];
    }
}

// Main execution
try {
    echo "==============================================\n";
    echo "3x Daily Notification Sender\n";
    echo "Started at: " . date('Y-m-d H:i:s') . "\n";
    echo "==============================================\n\n";
    
    $database = new Database();
    $db = $database->connect();
    
    if (!$db) {
        throw new Exception("Failed to connect to database");
    }
    
    $sender = new ThreeTimesNotificationSender($db);
    $result = $sender->processNotifications();
    
    echo "\n==============================================\n";
    echo "Completed at: " . date('Y-m-d H:i:s') . "\n";
    echo "==============================================\n";
    
    exit(0);
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    error_log("3x notification sender error: " . $e->getMessage());
    exit(1);
}
?>

