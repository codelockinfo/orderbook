<?php
/**
 * Test Push Notification - Detailed Error Reporting
 * 
 * This script tests sending a push notification and shows detailed error messages
 */

require_once __DIR__ . '/config/config.php';
requireLogin();

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Push Notification</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .success { background: #c8e6c9; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .error { background: #ffcdd2; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .code { background: #f5f5f5; padding: 10px; border-radius: 4px; font-family: monospace; white-space: pre-wrap; word-break: break-all; }
        button { background: #1976d2; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 10px 5px; }
        button:hover { background: #1565c0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test Push Notification</h1>
        
        <?php
        if (isset($_GET['test'])) {
            try {
                $database = new Database();
                $db = $database->connect();
                
                $userId = getCurrentUserId();
                
                // Get user's subscriptions
                $stmt = $db->prepare("SELECT * FROM push_subscriptions WHERE user_id = ? LIMIT 1");
                $stmt->execute([$userId]);
                $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$subscription) {
                    echo '<div class="error">';
                    echo '<strong>❌ No subscription found for your user</strong><br>';
                    echo 'Please enable push notifications by clicking the bell icon first.';
                    echo '</div>';
                } else {
                    echo '<div class="info">';
                    echo '<strong>📱 Testing with subscription:</strong><br>';
                    echo 'Endpoint: ' . htmlspecialchars(substr($subscription['endpoint'], 0, 80)) . '...<br>';
                    echo 'Has p256dh key: ' . (!empty($subscription['p256dh_key']) ? 'Yes' : 'No') . '<br>';
                    echo 'Has auth key: ' . (!empty($subscription['auth_key']) ? 'Yes' : 'No') . '<br>';
                    echo '</div>';
                    
                    // Load web-push library
                    $autoloadPath = __DIR__ . '/vendor/autoload.php';
                    if (file_exists($autoloadPath)) {
                        require_once $autoloadPath;
                        
                        if (class_exists('Minishlink\WebPush\WebPush')) {
                            try {
                                $auth = [
                                    'VAPID' => [
                                        'subject' => VAPID_SUBJECT,
                                        'publicKey' => VAPID_PUBLIC_KEY,
                                        'privateKey' => VAPID_PRIVATE_KEY,
                                    ],
                                ];
                                
                                $webPush = new \Minishlink\WebPush\WebPush($auth);
                                
                                $pushSubscription = \Minishlink\WebPush\Subscription::create([
                                    'endpoint' => $subscription['endpoint'],
                                    'keys' => [
                                        'p256dh' => $subscription['p256dh_key'],
                                        'auth' => $subscription['auth_key']
                                    ]
                                ]);
                                
                                $payload = json_encode([
                                    'title' => '🧪 Test Notification',
                                    'body' => 'This is a test notification from the diagnostic tool',
                                    'tag' => 'test-notification',
                                    'data' => ['url' => BASE_URL . 'index.php']
                                ]);
                                
                                echo '<div class="info">';
                                echo '<strong>📤 Sending test notification...</strong><br>';
                                echo '</div>';
                                
                                $result = $webPush->sendOneNotification($pushSubscription, $payload);
                                
                                if ($result->isSuccess()) {
                                    echo '<div class="success">';
                                    echo '<strong>✅ Notification sent successfully!</strong><br>';
                                    echo 'You should receive a push notification in your browser.';
                                    echo '</div>';
                                } else {
                                    $reason = $result->getReason();
                                    $statusCode = method_exists($result, 'getStatusCode') ? $result->getStatusCode() : 'unknown';
                                    
                                    echo '<div class="error">';
                                    echo '<strong>❌ Failed to send notification</strong><br>';
                                    echo 'Status Code: ' . htmlspecialchars($statusCode) . '<br>';
                                    echo 'Reason: ' . htmlspecialchars($reason) . '<br><br>';
                                    
                                    if ($statusCode == 410) {
                                        echo '<strong>⚠️ Subscription expired or invalid</strong><br>';
                                        echo 'The subscription endpoint is no longer valid. User needs to re-enable notifications.';
                                    } elseif ($statusCode == 401 || $statusCode == 403) {
                                        echo '<strong>⚠️ Authentication error</strong><br>';
                                        echo 'Check if VAPID keys are correct and match between frontend and backend.';
                                    } elseif ($statusCode == 413) {
                                        echo '<strong>⚠️ Payload too large</strong><br>';
                                        echo 'The notification payload is too big.';
                                    } else {
                                        echo '<strong>⚠️ Unknown error</strong><br>';
                                        echo 'Check server error logs for more details.';
                                    }
                                    echo '</div>';
                                    
                                    // Show full error details
                                    echo '<div class="code">';
                                    echo 'Full Error Details:\n';
                                    echo 'Status: ' . htmlspecialchars($statusCode) . '\n';
                                    echo 'Reason: ' . htmlspecialchars($reason) . '\n';
                                    if (method_exists($result, 'getResponse')) {
                                        $response = $result->getResponse();
                                        if ($response) {
                                            echo 'Response: ' . htmlspecialchars(print_r($response, true));
                                        }
                                    }
                                    echo '</div>';
                                }
                                
                            } catch (Exception $e) {
                                echo '<div class="error">';
                                echo '<strong>❌ Exception occurred:</strong><br>';
                                echo htmlspecialchars($e->getMessage()) . '<br>';
                                echo '<strong>Stack trace:</strong><br>';
                                echo '<div class="code">' . htmlspecialchars($e->getTraceAsString()) . '</div>';
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="error">';
                            echo '<strong>❌ Web Push library class not found</strong>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="error">';
                        echo '<strong>❌ Vendor directory not found</strong><br>';
                        echo 'Run: composer install';
                        echo '</div>';
                    }
                }
                
            } catch (Exception $e) {
                echo '<div class="error">';
                echo '<strong>❌ Error:</strong> ' . htmlspecialchars($e->getMessage());
                echo '</div>';
            }
        } else {
            echo '<div class="info">';
            echo '<strong>This will test sending a push notification to your device.</strong><br><br>';
            echo 'Make sure you have push notifications enabled (click the bell icon).';
            echo '</div>';
            echo '<a href="?test=1"><button>🧪 Test Push Notification</button></a>';
        }
        ?>
        
        <div class="info" style="margin-top: 20px;">
            <strong>Other Tests:</strong><br>
            <a href="check-notification-setup.php"><button>🔍 Check Setup</button></a>
            <a href="cron/auto-trigger.php"><button>🔔 Test Auto-Trigger</button></a>
        </div>
    </div>
</body>
</html>

