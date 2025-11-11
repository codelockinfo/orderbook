<?php
/**
 * Admin Panel - Manual Notification Trigger
 * 
 * Allows admin to manually trigger notifications from the website
 */
require_once __DIR__ . '/config/config.php';
requireLogin();

// Only allow admin users (you can add role check here)
$userId = getCurrentUserId();

require_once __DIR__ . '/config/database.php';
$database = new Database();
$db = $database->connect();

// Handle notification trigger
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trigger'])) {
    require_once __DIR__ . '/cron/send-3x-daily-notifications.php';
    
    ob_start();
    $sender = new ThreeTimesNotificationSender($db);
    $result = $sender->processNotifications();
    $output = ob_get_clean();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Trigger Notifications</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="header-content">
                <h1><i class="fas fa-cog"></i> Admin - Notification Control</h1>
                <button onclick="window.location.href='index.php'" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </button>
            </div>
        </header>
        
        <main>
            <!-- Trigger Section -->
            <div class="filters-section">
                <h3><i class="fas fa-bell"></i> Manual Notification Trigger</h3>
                
                <div style="padding: 20px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 8px; margin-bottom: 20px;">
                    <strong>⚠️ Manual Trigger:</strong><br>
                    This button triggers the same script that runs automatically via scheduled tasks.<br>
                    Use this to test or manually send notifications if needed.
                </div>
                
                <form method="POST" style="margin-bottom: 20px;">
                    <button type="submit" name="trigger" class="btn btn-primary" style="font-size: 16px; padding: 15px 30px;">
                        <i class="fas fa-paper-plane"></i> Trigger Notifications Now
                    </button>
                </form>
                
                <?php if (isset($output)): ?>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-top: 20px;">
                        <h4>Execution Result:</h4>
                        <pre style="background: #fff; padding: 15px; border-radius: 5px; overflow-x: auto; font-family: monospace; font-size: 12px; line-height: 1.5;"><?php echo htmlspecialchars($output); ?></pre>
                        
                        <?php if (isset($result)): ?>
                            <div style="margin-top: 15px; padding: 15px; background: <?php echo $result['sent'] > 0 ? '#d4edda' : '#fff3cd'; ?>; border-radius: 8px;">
                                <strong>Summary:</strong><br>
                                - Orders Processed: <?php echo $result['processed']; ?><br>
                                - Notifications Sent: <?php echo $result['sent']; ?><br>
                                - Failed: <?php echo $result['failed']; ?><br>
                                <?php if (isset($result['period'])): ?>
                                - Period: <?php echo $result['period']; ?><br>
                                - Reminder Number: <?php echo $result['reminderNumber'] ?? 'N/A'; ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Web Cron URL Section -->
            <div class="filters-section" style="margin-top: 20px;">
                <h3><i class="fas fa-link"></i> Web Cron Setup</h3>
                
                <div style="padding: 20px;">
                    <p style="margin-bottom: 15px;">
                        <strong>For automatic notifications without Windows Task Scheduler,</strong> use a free web cron service:
                    </p>
                    
                    <div style="background: #e7f3ff; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                        <strong>Your Web Cron URL:</strong><br>
                        <code style="background: #fff; padding: 8px; display: block; margin-top: 10px; border-radius: 4px; overflow-x: auto;">
                            http://localhost/orderbook/cron/web-cron.php?key=change-this-to-random-string-12345
                        </code>
                        <small style="color: #666; display: block; margin-top: 10px;">
                            ⚠️ Make sure to change the secret key in cron/web-cron.php first!
                        </small>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                        <strong>Free Web Cron Services:</strong>
                        <ul style="margin-top: 10px; line-height: 2;">
                            <li><a href="https://cron-job.org" target="_blank" style="color: #667eea;">cron-job.org</a> - Free, reliable</li>
                            <li><a href="https://www.easycron.com" target="_blank" style="color: #667eea;">easycron.com</a> - Free tier available</li>
                            <li><a href="https://console.cron-job.org" target="_blank" style="color: #667eea;">console.cron-job.org</a> - Modern interface</li>
                        </ul>
                    </div>
                    
                    <div style="margin-top: 15px; padding: 15px; background: #fff3cd; border-radius: 8px;">
                        <strong>Setup Instructions:</strong>
                        <ol style="line-height: 2; margin-top: 10px;">
                            <li>Register at any free cron service</li>
                            <li>Create 3 cron jobs with your URL above</li>
                            <li>Set schedules: 8:00 AM, 2:00 PM, 8:00 PM daily</li>
                            <li>Done! Notifications will run automatically</li>
                        </ol>
                    </div>
                </div>
            </div>
            
            <!-- Current Status -->
            <div class="filters-section" style="margin-top: 20px;">
                <h3><i class="fas fa-info-circle"></i> Current Status</h3>
                
                <div style="padding: 20px;">
                    <?php
                    // Check for tomorrow's orders
                    $tomorrow = date('Y-m-d', strtotime('+1 day'));
                    $stmt = $db->prepare("SELECT COUNT(*) as count FROM orders WHERE order_date = ? AND is_deleted = 0");
                    $stmt->execute([$tomorrow]);
                    $tomorrowCount = $stmt->fetch()['count'];
                    
                    // Check recent notifications
                    $stmt = $db->prepare("SELECT COUNT(*) as count FROM notification_logs WHERE DATE(sent_at) = CURDATE()");
                    $stmt->execute();
                    $todayNotifications = $stmt->fetch()['count'];
                    ?>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <div style="background: <?php echo $tomorrowCount > 0 ? '#d4edda' : '#f8f9fa'; ?>; padding: 20px; border-radius: 10px; text-align: center;">
                            <div style="font-size: 36px; font-weight: bold; color: #667eea;">
                                <?php echo $tomorrowCount; ?>
                            </div>
                            <div style="margin-top: 5px;">Orders for Tomorrow</div>
                        </div>
                        
                        <div style="background: <?php echo $todayNotifications > 0 ? '#d4edda' : '#f8f9fa'; ?>; padding: 20px; border-radius: 10px; text-align: center;">
                            <div style="font-size: 36px; font-weight: bold; color: #667eea;">
                                <?php echo $todayNotifications; ?>
                            </div>
                            <div style="margin-top: 5px;">Notifications Today</div>
                        </div>
                        
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; text-align: center;">
                            <div style="font-size: 24px; font-weight: bold; color: #667eea;">
                                <?php echo date('g:i A'); ?>
                            </div>
                            <div style="margin-top: 5px;">Current Time</div>
                            <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                <?php 
                                $hour = (int)date('H');
                                if ($hour >= 8 && $hour < 13) echo '🌅 Morning Period';
                                elseif ($hour >= 13 && $hour < 19) echo '☀️ Afternoon Period';
                                elseif ($hour >= 19 && $hour < 23) echo '🌙 Evening Period';
                                else echo '🌑 Outside Notification Windows';
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

