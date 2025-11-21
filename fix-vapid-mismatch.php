<?php
/**
 * Fix VAPID Key Mismatch
 * 
 * This script helps fix the issue where subscriptions were created with old VAPID keys
 * Users need to re-enable notifications with the new keys
 */

require_once __DIR__ . '/config/config.php';
requireLogin();

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix VAPID Key Mismatch</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .success { background: #c8e6c9; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .error { background: #ffcdd2; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .warning { background: #fff3cd; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 4px; margin: 10px 0; }
        button { background: #1976d2; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 10px 5px; }
        button.danger { background: #d32f2f; }
        button:hover { opacity: 0.9; }
        .code { background: #f5f5f5; padding: 10px; border-radius: 4px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Fix VAPID Key Mismatch</h1>
        
        <?php
        $database = new Database();
        $db = $database->connect();
        
        // Show current VAPID keys
        echo '<div class="info">';
        echo '<strong>Current VAPID Keys:</strong><br>';
        echo 'Public Key: <code>' . htmlspecialchars(substr(VAPID_PUBLIC_KEY, 0, 30)) . '...</code><br>';
        echo 'Private Key: ' . (empty(VAPID_PRIVATE_KEY) ? '<span style="color: red;">Missing!</span>' : '<span style="color: green;">Configured</span>');
        echo '</div>';
        
        // Check subscriptions
        $stmt = $db->query("SELECT COUNT(*) as count FROM push_subscriptions");
        $totalSubs = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo '<div class="info">';
        echo '<strong>Current Subscriptions:</strong> ' . $totalSubs;
        echo '</div>';
        
        // Handle clearing subscriptions
        if (isset($_POST['clear_subscriptions']) && $_POST['confirm'] === 'yes') {
            try {
                $stmt = $db->prepare("DELETE FROM push_subscriptions");
                $stmt->execute();
                $deleted = $stmt->rowCount();
                
                echo '<div class="success">';
                echo '<strong>✅ Successfully deleted ' . $deleted . ' old subscription(s)</strong><br>';
                echo 'Users will need to re-enable notifications by clicking the bell icon.';
                echo '</div>';
            } catch (Exception $e) {
                echo '<div class="error">';
                echo '<strong>❌ Error:</strong> ' . htmlspecialchars($e->getMessage());
                echo '</div>';
            }
        }
        
        // Show explanation
        echo '<div class="warning">';
        echo '<strong>⚠️ Problem Identified:</strong><br>';
        echo 'The error "403 Forbidden: VAPID credentials do not correspond" means that:<br>';
        echo '• Subscriptions in database were created with OLD VAPID keys<br>';
        echo '• Current VAPID keys in config are DIFFERENT<br>';
        echo '• Users need to re-subscribe with the new keys<br><br>';
        echo '<strong>Solution:</strong><br>';
        echo '1. Clear old subscriptions (button below)<br>';
        echo '2. Ask users to re-enable notifications (click bell icon again)<br>';
        echo '3. New subscriptions will use the correct keys';
        echo '</div>';
        
        if ($totalSubs > 0) {
            echo '<form method="POST" onsubmit="return confirm(\'Are you sure? This will delete all subscriptions and users will need to re-enable notifications.\');">';
            echo '<div class="warning">';
            echo '<strong>Clear All Subscriptions:</strong><br>';
            echo 'This will delete all existing subscriptions. Users will need to click the bell icon again to re-enable notifications.<br><br>';
            echo '<label><input type="checkbox" name="confirm" value="yes" required> I understand - clear all subscriptions</label><br><br>';
            echo '<button type="submit" name="clear_subscriptions" class="danger">🗑️ Clear All Subscriptions</button>';
            echo '</div>';
            echo '</form>';
        } else {
            echo '<div class="success">';
            echo '<strong>✅ No subscriptions to clear</strong><br>';
            echo 'Users can now enable notifications with the correct VAPID keys.';
            echo '</div>';
        }
        
        // Show instructions for users
        echo '<div class="info">';
        echo '<strong>📋 Instructions for Users:</strong><br>';
        echo '1. Login to the website<br>';
        echo '2. Click the notification bell icon 🔔<br>';
        echo '3. If already enabled, click it again to disable, then click again to re-enable<br>';
        echo '4. Grant browser permission when asked<br>';
        echo '5. Subscription will be saved with the correct VAPID keys';
        echo '</div>';
        ?>
        
        <div class="info" style="margin-top: 20px;">
            <strong>Other Tools:</strong><br>
            <a href="check-notification-setup.php"><button>🔍 Check Setup</button></a>
            <a href="test-push-notification.php"><button>🧪 Test Notification</button></a>
            <a href="cron/auto-trigger.php"><button>🔔 Test Auto-Trigger</button></a>
        </div>
    </div>
</body>
</html>

