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

try {
    $database = new Database();
    $db = $database->connect();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    // Include the notification sender
    require_once __DIR__ . '/send-3x-daily-notifications.php';
    
    // Suppress output and capture it
    ob_start();
    $sender = new ThreeTimesNotificationSender($db);
    $result = $sender->processNotifications();
    $output = ob_get_clean();
    
    // Return JSON response
    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'result' => $result,
        'message' => 'Auto-trigger completed successfully'
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

