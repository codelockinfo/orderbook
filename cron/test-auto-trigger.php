<?php
/**
 * Test Auto-Trigger Endpoint (Allows testing outside notification windows)
 * 
 * This version allows testing at any time by temporarily adjusting time windows
 * Use this for testing only!
 */

header('Content-Type: application/json');

// Allow from same origin only
$allowed_origin = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
header("Access-Control-Allow-Origin: $allowed_origin");

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->connect();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    // Include the notification sender
    require_once __DIR__ . '/send-3x-daily-notifications.php';
    
    // Create a test version that allows any time
    class TestNotificationSender extends ThreeTimesNotificationSender {
        // Override to allow testing at any time
        public function getCurrentNotificationPeriod() {
            $currentHour = (int)date('H');
            
            // For testing: allow any hour by mapping to a period
            if ($currentHour >= 0 && $currentHour < 6) {
                return 1; // Treat early morning as morning period
            } elseif ($currentHour >= 6 && $currentHour < 12) {
                return 1; // Morning
            } elseif ($currentHour >= 12 && $currentHour < 18) {
                return 2; // Afternoon
            } else {
                return 3; // Evening
            }
        }
    }
    
    // Suppress output and capture it
    ob_start();
    $sender = new TestNotificationSender($db);
    $result = $sender->processNotifications();
    $output = ob_get_clean();
    
    // Return JSON response
    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'current_hour' => (int)date('H'),
        'result' => $result,
        'message' => 'Test auto-trigger completed successfully',
        'note' => 'This is a test version that allows testing at any time'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>

