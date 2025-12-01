<?php
/**
 * Auto-Trigger Endpoint
 * 
 * This is called automatically by JavaScript when user has notifications enabled
 * Runs in the background every hour to check and send notifications
 */

header('Content-Type: application/json');

// Allow from same origin only
$allowed_origin = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
header("Access-Control-Allow-Origin: $allowed_origin");

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Include Composer autoloader (required for web-push library)
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    error_log('WARNING: vendor/autoload.php not found. Composer dependencies may not be installed.');
}

// Include FCM Notification Sender
require_once __DIR__ . '/../backend/FCMNotificationSender.php';

// Ensure timezone is set to India (Asia/Kolkata)
if (!ini_get('date.timezone') || ini_get('date.timezone') !== 'Asia/Kolkata') {
    date_default_timezone_set('Asia/Kolkata');
}

try {
    $database = new Database();
    $db = $database->connect();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    // Include the notification sender
    require_once __DIR__ . '/send-3x-daily-notifications.php';
    
    // Capture output from notification sender
    ob_start();
    $sender = new ThreeTimesNotificationSender($db);
    $result = $sender->processNotifications();
    $output = ob_get_clean();
    
    // Note: FCM notifications are not sent here because order-specific notifications
    // are already being sent by ThreeTimesNotificationSender with full order details.
    // If you need FCM notifications, they should be sent per-order with order details.
    
    // Return JSON response with output
    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'result' => $result,
        'output' => $output, // Include the detailed output
        'message' => 'Auto-trigger completed successfully',
        'orders_processed' => $result['processed'] ?? 0,
        'notifications_sent' => $result['sent'] ?? 0,
        'period' => $result['period'] ?? 'none'
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>

