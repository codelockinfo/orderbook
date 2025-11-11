<?php
/**
 * Automatic Notification Service
 * 
 * Handles automatic sending of 3x daily notifications when orders are created/updated
 * This service is called automatically after order creation or modification
 */

class NotificationService {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Check if we should send notifications for this order automatically
     * Sends appropriate notifications based on order date and current time
     */
    public function processOrderNotifications($orderId, $userId) {
        try {
            // Get the order details
            $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
            $stmt->execute([$orderId, $userId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                return ['success' => false, 'message' => 'Order not found'];
            }
            
            // Check if order is deleted or completed
            if ($order['is_deleted'] == 1 || $order['status'] == 'Completed' || $order['status'] == 'Cancelled') {
                return ['success' => false, 'message' => 'Order is not active', 'skipped' => true];
            }
            
            $orderDate = $order['order_date'];
            $today = date('Y-m-d');
            $tomorrow = date('Y-m-d', strtotime('+1 day'));
            
            // Check if order is for tomorrow (eligible for 3x notifications)
            if ($orderDate === $tomorrow) {
                return $this->sendAppropriateReminder($order, $userId);
            }
            
            // Check if order is for today (send immediate notification)
            if ($orderDate === $today) {
                return $this->sendTodayNotification($order, $userId);
            }
            
            // Order is in the future (more than 1 day away)
            return [
                'success' => true, 
                'message' => 'Order scheduled successfully. Notifications will be sent 1 day before.',
                'scheduled' => true,
                'orderDate' => $orderDate
            ];
            
        } catch (Exception $e) {
            error_log("NotificationService error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Notification processing failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Send appropriate reminder based on current time
     */
    private function sendAppropriateReminder($order, $userId) {
        $currentHour = (int)date('H');
        $reminderNumber = $this->determineReminderNumber($currentHour);
        
        if ($reminderNumber === 0) {
            return [
                'success' => true,
                'message' => 'Order created for tomorrow. Notifications will be sent during notification windows.',
                'scheduled' => true
            ];
        }
        
        // Check if this reminder was already sent
        $reminderField = "notification_{$reminderNumber}_sent";
        if ($order[$reminderField] == 1) {
            // Already sent, check if we should send the next one
            $nextReminder = $reminderNumber + 1;
            if ($nextReminder <= 3) {
                $nextField = "notification_{$nextReminder}_sent";
                if ($order[$nextField] == 0) {
                    $reminderNumber = $nextReminder;
                } else {
                    return [
                        'success' => true,
                        'message' => 'All reminders already sent for this order',
                        'allSent' => true
                    ];
                }
            }
        }
        
        // Send the notification
        return $this->sendReminder($order, $userId, $reminderNumber);
    }
    
    /**
     * Determine which reminder number based on current hour
     */
    private function determineReminderNumber($hour) {
        if ($hour >= 8 && $hour < 13) {
            return 1; // Morning
        } elseif ($hour >= 13 && $hour < 19) {
            return 2; // Afternoon
        } elseif ($hour >= 19 && $hour < 23) {
            return 3; // Evening
        }
        return 0; // Outside notification windows
    }
    
    /**
     * Send a specific reminder
     */
    private function sendReminder($order, $userId, $reminderNumber) {
        $periodNames = [1 => 'Morning', 2 => 'Afternoon', 3 => 'Evening'];
        $periodEmojis = [1 => '🌅', 2 => '☀️', 3 => '🌙'];
        $reminderTexts = [
            1 => "Morning reminder: Your order is scheduled for TOMORROW!",
            2 => "Afternoon reminder: Don't forget your order tomorrow!",
            3 => "Evening reminder: Your order is coming up tomorrow!"
        ];
        
        $periodName = $periodNames[$reminderNumber];
        $periodEmoji = $periodEmojis[$reminderNumber];
        $reminderText = $reminderTexts[$reminderNumber];
        
        // Get user's push subscriptions
        $subscriptions = $this->getUserSubscriptions($userId);
        
        if (empty($subscriptions)) {
            // Mark as sent anyway (user might enable notifications later)
            $this->markReminderSent($order['id'], $reminderNumber);
            
            return [
                'success' => true,
                'message' => "Order created for tomorrow. Enable push notifications to receive {$periodName} reminders.",
                'noSubscriptions' => true,
                'reminderScheduled' => $reminderNumber
            ];
        }
        
        // Prepare notification payload
        $orderNumber = $order['order_number'];
        $orderDate = date('l, F j, Y', strtotime($order['order_date']));
        $orderTime = date('g:i A', strtotime($order['order_time']));
        
        $payload = [
            'title' => "{$periodEmoji} Order Reminder #{$reminderNumber}",
            'body' => "{$reminderText} Order #{$orderNumber} on {$orderDate} at {$orderTime}",
            'tag' => "order-reminder-{$reminderNumber}-{$order['id']}",
            'data' => [
                'url' => '/orderbook/index.php',
                'orderId' => $order['id'],
                'orderNumber' => $orderNumber,
                'orderDate' => $order['order_date'],
                'orderTime' => $order['order_time'],
                'reminderNumber' => $reminderNumber
            ],
            'requireInteraction' => false,
            'vibrate' => [200, 100, 200]
        ];
        
        // Send to all subscriptions
        $sentCount = 0;
        foreach ($subscriptions as $subscription) {
            $result = $this->sendPushNotification($subscription, $payload);
            if ($result) {
                $sentCount++;
            }
        }
        
        // Mark as sent and log
        $this->markReminderSent($order['id'], $reminderNumber);
        $this->logNotification($userId, $order['id'], $payload['body'], $reminderNumber, 'sent');
        
        return [
            'success' => true,
            'message' => "{$periodEmoji} {$periodName} reminder sent automatically!",
            'reminderNumber' => $reminderNumber,
            'reminderPeriod' => $periodName,
            'sentToDevices' => $sentCount,
            'autoSent' => true
        ];
    }
    
    /**
     * Send notification for today's order
     */
    private function sendTodayNotification($order, $userId) {
        $subscriptions = $this->getUserSubscriptions($userId);
        
        if (empty($subscriptions)) {
            return [
                'success' => true,
                'message' => 'Order created for today. Enable push notifications to receive alerts.',
                'noSubscriptions' => true
            ];
        }
        
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
        } else {
            $timeText = "in " . round($hoursUntil, 1) . " hours";
        }
        
        $payload = [
            'title' => '🔔 Order Due Today!',
            'body' => "Your order #{$orderNumber} is scheduled for TODAY at {$orderTime} ({$timeText})",
            'tag' => "order-today-{$order['id']}",
            'data' => [
                'url' => '/orderbook/index.php',
                'orderId' => $order['id'],
                'orderNumber' => $orderNumber
            ]
        ];
        
        $sentCount = 0;
        foreach ($subscriptions as $subscription) {
            $result = $this->sendPushNotification($subscription, $payload);
            if ($result) {
                $sentCount++;
            }
        }
        
        $this->logNotification($userId, $order['id'], $payload['body'], null, 'sent');
        
        return [
            'success' => true,
            'message' => '🔔 Today notification sent automatically!',
            'sentToDevices' => $sentCount,
            'autoSent' => true,
            'orderToday' => true
        ];
    }
    
    /**
     * Get user's push subscriptions
     */
    private function getUserSubscriptions($userId) {
        $stmt = $this->db->prepare("SELECT * FROM push_subscriptions WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Send push notification (simplified version)
     */
    private function sendPushNotification($subscription, $payload) {
        // In production, use web-push-php library
        // For now, log and return true
        error_log("Auto-notification sent: " . $payload['title'] . " - " . $payload['body']);
        return true;
    }
    
    /**
     * Mark reminder as sent
     */
    private function markReminderSent($orderId, $reminderNumber) {
        $reminderField = "notification_{$reminderNumber}_sent";
        $timestampField = "notification_{$reminderNumber}_sent_at";
        
        $sql = "UPDATE orders 
                SET {$reminderField} = 1, 
                    {$timestampField} = NOW() 
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$orderId]);
    }
    
    /**
     * Log notification
     */
    private function logNotification($userId, $orderId, $message, $reminderNumber, $status = 'sent') {
        $notificationTypes = [
            1 => 'morning-reminder',
            2 => 'afternoon-reminder',
            3 => 'evening-reminder'
        ];
        
        $notificationType = $notificationTypes[$reminderNumber] ?? 'auto-reminder';
        
        $sql = "INSERT INTO notification_logs 
                (user_id, order_id, notification_type, reminder_number, message, status) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $userId, 
            $orderId, 
            $notificationType, 
            $reminderNumber, 
            $message, 
            $status
        ]);
    }
}
?>

