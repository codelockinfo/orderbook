<?php
/**
 * Test Timezone
 * Check if server timezone matches India time
 */

require_once __DIR__ . '/config/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Timezone Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .success { background: #c8e6c9; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .warning { background: #fff3cd; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .time { font-size: 24px; font-weight: bold; color: #1976d2; }
        .hour { font-size: 20px; color: #388e3c; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🕐 Timezone Test</h1>
        
        <div class="info">
            <strong>Current Timezone:</strong> <?php echo date_default_timezone_get(); ?><br>
            <strong>Current Server Time:</strong> <span class="time"><?php echo date('Y-m-d H:i:s'); ?></span><br>
            <strong>Current Hour:</strong> <span class="hour"><?php echo date('H'); ?>:00</span>
        </div>
        
        <?php
        $currentHour = (int)date('H');
        $timezone = date_default_timezone_get();
        
        // Check if in notification windows
        $inMorning = ($currentHour >= 8 && $currentHour < 13);
        $inAfternoon = ($currentHour >= 13 && $currentHour < 19);
        $inEvening = ($currentHour >= 19 && $currentHour < 23);
        $inWindow = $inMorning || $inAfternoon || $inEvening;
        
        if ($timezone === 'Asia/Kolkata') {
            echo '<div class="success">';
            echo '<strong>✅ Timezone is correct!</strong><br>';
            echo 'Server is using India timezone (Asia/Kolkata)';
            echo '</div>';
        } else {
            echo '<div class="warning">';
            echo '<strong>⚠️ Timezone mismatch!</strong><br>';
            echo 'Server timezone is: ' . $timezone . '<br>';
            echo 'Should be: Asia/Kolkata (India)';
            echo '</div>';
        }
        
        if ($inWindow) {
            echo '<div class="success">';
            echo '<strong>✅ Currently in notification window!</strong><br>';
            if ($inMorning) {
                echo '🌅 Morning window (8 AM - 1 PM) - Notifications will be sent';
            } elseif ($inAfternoon) {
                echo '☀️ Afternoon window (1 PM - 7 PM) - Notifications will be sent';
            } elseif ($inEvening) {
                echo '🌙 Evening window (7 PM - 11 PM) - Notifications will be sent';
            }
            echo '</div>';
        } else {
            echo '<div class="warning">';
            echo '<strong>⏰ Outside notification windows</strong><br>';
            echo 'Notification windows:<br>';
            echo '• Morning: 8:00 AM - 1:00 PM<br>';
            echo '• Afternoon: 1:00 PM - 7:00 PM<br>';
            echo '• Evening: 7:00 PM - 11:00 PM';
            echo '</div>';
        }
        ?>
        
        <div class="info" style="margin-top: 20px;">
            <strong>Test Notification:</strong><br>
            <a href="cron/auto-trigger.php" target="_blank" style="color: #1976d2; text-decoration: none;">
                Click here to test notification trigger →
            </a>
        </div>
    </div>
</body>
</html>

