<?php
/**
 * Notification Setup Diagnostic
 * Check if everything is configured correctly for notifications
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Notification Setup Check</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .check { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #c8e6c9; color: #2e7d32; }
        .error { background: #ffcdd2; color: #c62828; }
        .warning { background: #fff3cd; color: #856404; }
        .info { background: #e3f2fd; color: #1565c0; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Notification Setup Diagnostic</h1>
        
        <?php
        $issues = [];
        $warnings = [];
        
        // Check Web Push Library
        echo '<h2>1. Web Push Library</h2>';
        $autoloadPath = __DIR__ . '/vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
            if (class_exists('Minishlink\WebPush\WebPush')) {
                echo '<div class="check success">✅ Web Push library installed</div>';
            } else {
                echo '<div class="check error">❌ Web Push library class not found</div>';
                $issues[] = 'Web Push library class not found';
            }
        } else {
            echo '<div class="check error">❌ Vendor directory not found</div>';
            echo '<div class="check warning">💡 Run: <code>composer install</code> on your server</div>';
            $issues[] = 'Web Push library not installed';
        }
        
        // Check VAPID Keys
        echo '<h2>2. VAPID Keys Configuration</h2>';
        if (defined('VAPID_PUBLIC_KEY') && !empty(VAPID_PUBLIC_KEY)) {
            $keyPreview = substr(VAPID_PUBLIC_KEY, 0, 20) . '...';
            echo '<div class="check success">✅ VAPID Public Key configured: <code>' . htmlspecialchars($keyPreview) . '</code></div>';
        } else {
            echo '<div class="check error">❌ VAPID Public Key missing</div>';
            echo '<div class="check warning">💡 Generate keys: <code>php cron/generate-vapid-keys.php</code></div>';
            $issues[] = 'VAPID Public Key not configured';
        }
        
        if (defined('VAPID_PRIVATE_KEY') && !empty(VAPID_PRIVATE_KEY)) {
            echo '<div class="check success">✅ VAPID Private Key configured</div>';
        } else {
            echo '<div class="check error">❌ VAPID Private Key missing</div>';
            $issues[] = 'VAPID Private Key not configured';
        }
        
        if (defined('VAPID_SUBJECT') && !empty(VAPID_SUBJECT)) {
            echo '<div class="check success">✅ VAPID Subject configured: <code>' . htmlspecialchars(VAPID_SUBJECT) . '</code></div>';
        } else {
            echo '<div class="check warning">⚠️ VAPID Subject not set (optional but recommended)</div>';
        }
        
        // Check Database
        echo '<h2>3. Database & Subscriptions</h2>';
        try {
            $database = new Database();
            $db = $database->connect();
            
            // Check subscriptions
            $stmt = $db->query("SELECT COUNT(*) as count FROM push_subscriptions");
            $subCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo '<div class="check info">📱 Total push subscriptions: <strong>' . $subCount . '</strong></div>';
            
            if ($subCount == 0) {
                echo '<div class="check warning">⚠️ No users have enabled push notifications yet</div>';
                echo '<div class="check info">💡 Users need to click the notification bell icon to enable</div>';
            }
            
            // Check orders for tomorrow
            $tomorrow = date('Y-m-d', strtotime('+1 day'));
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM orders WHERE order_date = ? AND is_deleted = 0 AND status IN ('Pending', 'Processing')");
            $stmt->execute([$tomorrow]);
            $orderCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo '<div class="check info">📦 Orders scheduled for tomorrow (' . $tomorrow . '): <strong>' . $orderCount . '</strong></div>';
            
            // Check users with subscriptions
            $stmt = $db->query("
                SELECT u.username, COUNT(ps.id) as sub_count 
                FROM users u 
                LEFT JOIN push_subscriptions ps ON u.id = ps.user_id 
                GROUP BY u.id, u.username
                HAVING sub_count > 0
            ");
            $usersWithSubs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($usersWithSubs)) {
                echo '<div class="check success">✅ Users with push notifications enabled:</div>';
                echo '<ul>';
                foreach ($usersWithSubs as $user) {
                    echo '<li><strong>' . htmlspecialchars($user['username']) . '</strong> - ' . $user['sub_count'] . ' device(s)</li>';
                }
                echo '</ul>';
            }
            
            // Check recent notification logs
            $stmt = $db->query("SELECT * FROM notification_logs ORDER BY created_at DESC LIMIT 5");
            $recentLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($recentLogs)) {
                echo '<div class="check info">📬 Recent notification attempts:</div>';
                echo '<ul>';
                foreach ($recentLogs as $log) {
                    $statusClass = $log['status'] === 'sent' ? 'success' : 'error';
                    echo '<li class="check ' . $statusClass . '">';
                    echo htmlspecialchars($log['message']) . ' - <strong>' . $log['status'] . '</strong>';
                    echo ' (' . $log['created_at'] . ')';
                    echo '</li>';
                }
                echo '</ul>';
            }
            
        } catch (Exception $e) {
            echo '<div class="check error">❌ Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            $issues[] = 'Database connection error';
        }
        
        // Summary
        echo '<h2>4. Summary</h2>';
        if (empty($issues)) {
            echo '<div class="check success">';
            echo '<strong>✅ Everything looks good!</strong><br>';
            echo 'All required components are configured. If notifications still fail, check server error logs for detailed error messages.';
            echo '</div>';
        } else {
            echo '<div class="check error">';
            echo '<strong>❌ Issues Found:</strong><br>';
            echo '<ul>';
            foreach ($issues as $issue) {
                echo '<li>' . htmlspecialchars($issue) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        ?>
        
        <div class="check info" style="margin-top: 20px;">
            <strong>Test Notification:</strong><br>
            <a href="cron/auto-trigger.php" target="_blank" style="color: #1565c0;">
                Click here to test notification trigger →
            </a>
        </div>
    </div>
</body>
</html>

