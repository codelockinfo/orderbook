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
require_once __DIR__ . '/../config/config.php';

// Ensure timezone is set to India (Asia/Kolkata)
if (!ini_get('date.timezone') || ini_get('date.timezone') !== 'Asia/Kolkata') {
    date_default_timezone_set('Asia/Kolkata');
}

// Load web-push library if available
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

class ThreeTimesNotificationSender {
    private $db;
    private $webPush;
    
    // Notification time windows (24-hour format)
    const MORNING_START = 8;    // 8:00 AM
    const MORNING_END = 13;      // 1:00 PM
    
    const AFTERNOON_START = 13;  // 1:00 PM
    const AFTERNOON_END = 19;    // 7:00 PM
    
    const EVENING_START = 19;    // 7:00 PM
    const EVENING_END = 23;      // 11:00 PM
    
    public function __construct($db) {
        $this->db = $db;
        
        // Initialize web-push if library is available
        if (class_exists('Minishlink\WebPush\WebPush')) {
            try {
                $auth = [
                    'VAPID' => [
                        'subject' => VAPID_SUBJECT,
                        'publicKey' => VAPID_PUBLIC_KEY,
                        'privateKey' => VAPID_PRIVATE_KEY,
                    ],
                ];
                
                $this->webPush = new \Minishlink\WebPush\WebPush($auth);
                error_log('Web Push library initialized successfully');
            } catch (Exception $e) {
                error_log('Failed to initialize Web Push: ' . $e->getMessage());
                $this->webPush = null;
            }
        } else {
            error_log('Web Push library not found. Install with: composer require minishlink/web-push');
            $this->webPush = null;
        }
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
        // Ensure timezone is set correctly
        $currentTimezone = date_default_timezone_get();
        $currentDate = date('Y-m-d');
        $currentTime = date('H:i:s');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        
        echo "⏰ Timezone: {$currentTimezone}\n";
        echo "   Current date: {$currentDate}\n";
        echo "   Current time: {$currentTime}\n";
        echo "   Tomorrow date: {$tomorrow}\n\n";
        
        // Build SQL based on which notification period
        $notificationField = "notification_{$period}_sent";
        
        // Debug: Log what we're looking for
        echo "🔍 Searching for orders with:\n";
        echo "   - Date: {$tomorrow} (tomorrow)\n";
        echo "   - Notification field: {$notificationField}\n";
        echo "   - Status: Pending or Processing\n";
        echo "   - Not deleted\n\n";
        
        // First, let's check if there are ANY orders for tomorrow (for debugging)
        $checkSql = "SELECT COUNT(*) as total, 
                            SUM(CASE WHEN is_deleted = 0 THEN 1 ELSE 0 END) as not_deleted,
                            SUM(CASE WHEN status IN ('Pending', 'Processing') THEN 1 ELSE 0 END) as pending_or_processing,
                            SUM(CASE WHEN {$notificationField} = 0 OR {$notificationField} IS NULL THEN 1 ELSE 0 END) as notification_not_sent
                     FROM orders 
                     WHERE order_date = :tomorrow";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->execute(['tomorrow' => $tomorrow]);
        $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        echo "📊 Orders for tomorrow ({$tomorrow}):\n";
        echo "   - Total orders: " . ($checkResult['total'] ?? 0) . "\n";
        echo "   - Not deleted: " . ($checkResult['not_deleted'] ?? 0) . "\n";
        echo "   - Pending/Processing: " . ($checkResult['pending_or_processing'] ?? 0) . "\n";
        echo "   - Notification not sent: " . ($checkResult['notification_not_sent'] ?? 0) . "\n\n";
        
        // Main query - handle NULL values for notification fields
        $sql = "SELECT o.*, u.username, u.email 
                FROM orders o
                JOIN users u ON o.user_id = u.id
                WHERE o.order_date = :tomorrow
                AND (o.{$notificationField} = 0 OR o.{$notificationField} IS NULL)
                AND o.is_deleted = 0
                AND o.status IN ('Pending', 'Processing')
                ORDER BY o.order_time ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['tomorrow' => $tomorrow]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug: Show what we found
        if (count($orders) > 0) {
            echo "✅ Found " . count($orders) . " order(s) needing notification:\n";
            foreach ($orders as $order) {
                echo "   - Order #{$order['order_number']} (ID: {$order['id']}) - Status: {$order['status']}\n";
            }
        } else {
            echo "⚠️  No orders found matching all criteria.\n";
            // Let's see what orders exist for tomorrow
            $debugSql = "SELECT o.id, o.order_number, o.order_date, o.status, o.is_deleted, 
                                o.notification_1_sent, o.notification_2_sent, o.notification_3_sent
                         FROM orders o
                         WHERE o.order_date = :tomorrow
                         LIMIT 5";
            $debugStmt = $this->db->prepare($debugSql);
            $debugStmt->execute(['tomorrow' => $tomorrow]);
            $debugOrders = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($debugOrders) > 0) {
                echo "\n📋 Orders found for tomorrow (for debugging):\n";
                foreach ($debugOrders as $debugOrder) {
                    echo "   - Order #{$debugOrder['order_number']}:\n";
                    echo "     * Status: {$debugOrder['status']}\n";
                    echo "     * Deleted: " . ($debugOrder['is_deleted'] ? 'Yes' : 'No') . "\n";
                    echo "     * Notification 1 sent: " . ($debugOrder['notification_1_sent'] ?? 'NULL') . "\n";
                    echo "     * Notification 2 sent: " . ($debugOrder['notification_2_sent'] ?? 'NULL') . "\n";
                    echo "     * Notification 3 sent: " . ($debugOrder['notification_3_sent'] ?? 'NULL') . "\n";
                }
            }
        }
        echo "\n";
        
        return $orders;
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
        $p256dh = $subscription['p256dh_key'] ?? null;
        $auth = $subscription['auth_key'] ?? null;
        
        if (!$p256dh || !$auth) {
            error_log('Missing subscription keys (p256dh or auth)');
            return false;
        }
        
        $payloadJson = json_encode($payload);
        
        // Use web-push library if available
        if ($this->webPush !== null) {
            try {
                $pushSubscription = \Minishlink\WebPush\Subscription::create([
                    'endpoint' => $endpoint,
                    'keys' => [
                        'p256dh' => $p256dh,
                        'auth' => $auth
                    ]
                ]);
                
                $result = $this->webPush->sendOneNotification(
                    $pushSubscription,
                    $payloadJson
                );
                
                // Check result
                if ($result->isSuccess()) {
                    error_log("✓ Push notification sent successfully to: " . substr($endpoint, 0, 50) . "...");
                    return true;
                } else {
                    $reason = $result->getReason();
                    $statusCode = method_exists($result, 'getStatusCode') ? $result->getStatusCode() : 'unknown';
                    $errorMsg = "Failed to send push notification (Status: {$statusCode}): {$reason}";
                    error_log("✗ " . $errorMsg);
                    
                    // If subscription is invalid (410), we should remove it
                    if ($result->isSubscriptionExpired()) {
                        error_log("Subscription expired, removing from database");
                        $this->removeSubscription($subscription['id'] ?? null, $endpoint);
                    }
                    
                    // Return error message for better debugging
                    return $errorMsg;
                }
            } catch (Exception $e) {
                $errorMsg = "Exception: " . $e->getMessage();
                error_log("Error sending push notification: " . $errorMsg);
                return $errorMsg;
            }
        } else {
            // Fallback: log but don't actually send
            $errorMsg = "Web Push library not available. Install with: composer require minishlink/web-push";
            error_log("⚠ " . $errorMsg);
            error_log("Notification would be sent to: " . substr($endpoint, 0, 50) . "...");
            error_log("Payload: " . $payloadJson);
            return $errorMsg;
        }
    }
    
    /**
     * Remove expired/invalid subscription
     */
    private function removeSubscription($subscriptionId, $endpoint) {
        try {
            if ($subscriptionId) {
                $stmt = $this->db->prepare("DELETE FROM push_subscriptions WHERE id = ?");
                $stmt->execute([$subscriptionId]);
            } else if ($endpoint) {
                $stmt = $this->db->prepare("DELETE FROM push_subscriptions WHERE endpoint = ?");
                $stmt->execute([$endpoint]);
            }
        } catch (Exception $e) {
            error_log("Error removing subscription: " . $e->getMessage());
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
                echo "  - No subscriptions found for user {$order['username']} (ID: {$userId})\n";
                echo "    ⚠️  User needs to enable push notifications in their browser\n";
                $this->logNotification($userId, $orderId, "No subscriptions found", $period, 'failed');
                // Don't mark as sent if there are no subscriptions
                continue;
            }
            
            echo "  - Found " . count($subscriptions) . " subscription(s) for user\n";
            
            // Create period-specific message with order details
            $reminderText = [
                1 => "Morning reminder: Your order is scheduled for TOMORROW!",
                2 => "Afternoon reminder: Don't forget your order tomorrow!",
                3 => "Evening reminder: Your order is coming up tomorrow!"
            ];
            
            // Get base path for notification data (relative path)
            $basePath = parse_url(BASE_URL, PHP_URL_PATH);
            if (!$basePath || $basePath === '/') {
                $basePath = '';
            }
            $indexPath = rtrim($basePath, '/') . '/index.php';
            
            // Prepare notification payload with full order details
            // Format: Single line with all details (browser notifications don't support multi-line well)
            $notificationTitle = "{$periodEmoji} Order Reminder #{$period}";
            $notificationBody = "{$reminderText[$period]} Order #{$orderNumber} on {$orderDate} at {$orderTime}";
            
            $payload = [
                'title' => $notificationTitle,
                'body' => $notificationBody,
                'tag' => "order-reminder-{$period}-{$orderId}",
                'data' => [
                    'url' => $indexPath,
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
            $failedCount = 0;
            $errorMessages = [];
            
            foreach ($subscriptions as $subscription) {
                $result = $this->sendPushNotification($subscription, $payload);
                if ($result === true) {
                    $sentCount++;
                } else {
                    $failedCount++;
                    // Collect error messages if available
                    if (is_string($result)) {
                        $errorMessages[] = $result;
                    }
                }
            }
            
            if ($sentCount > 0) {
                echo "  ✓ Sent reminder #{$period} to {$sentCount} device(s)\n";
                $this->markNotificationSent($orderId, $period);
                $this->logNotification($userId, $orderId, $payload['body'], $period, 'sent');
                $totalSent++;
            } else {
                echo "  ✗ Failed to send to all devices ({$failedCount} device(s))\n";
                if (!empty($errorMessages)) {
                    foreach ($errorMessages as $error) {
                        echo "    Error: {$error}\n";
                    }
                }
                $this->logNotification($userId, $orderId, $payload['body'] . (empty($errorMessages) ? '' : ' - ' . implode(', ', $errorMessages)), $period, 'failed');
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

