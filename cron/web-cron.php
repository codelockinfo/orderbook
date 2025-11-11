<?php
/**
 * Web Cron Endpoint
 * 
 * Call this URL to trigger notifications:
 * http://localhost/orderbook/cron/web-cron.php?key=your-secret-key
 * 
 * Use a free web cron service to ping this URL automatically:
 * - https://cron-job.org (free)
 * - https://www.easycron.com (free tier)
 * - https://console.cron-job.org (free)
 * 
 * Set it to ping 3 times per day:
 * - 8:00 AM
 * - 2:00 PM  
 * - 8:00 PM
 */

// Security: Simple secret key check
$SECRET_KEY = 'change-this-to-random-string-12345'; // CHANGE THIS!

// Check if key is provided and matches
if (!isset($_GET['key']) || $_GET['key'] !== $SECRET_KEY) {
    http_response_code(403);
    die('Access denied. Invalid or missing key.');
}

// Include database connection
require_once __DIR__ . '/../config/database.php';

// Include the notification sender
require_once __DIR__ . '/send-3x-daily-notifications.php';

// Note: The send-3x-daily-notifications.php file already has its own execution logic
// If it's included, it will run automatically

// If you're running this via CLI, the script above handles everything
// If you're running via web (like we are now), we need to manually instantiate it

try {
    // Set headers for proper output
    header('Content-Type: text/plain; charset=utf-8');
    
    echo "==============================================\n";
    echo "Web Cron Triggered\n";
    echo "Time: " . date('Y-m-d H:i:s') . "\n";
    echo "==============================================\n\n";
    
    // Check if we're in CLI mode or web mode
    if (php_sapi_name() !== 'cli') {
        // We're in web mode, need to manually trigger
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
        
        // Return JSON for cron service
        if (isset($_GET['json'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'time' => date('Y-m-d H:i:s'),
                'result' => $result
            ]);
        }
    }
    
    exit(0);
    
} catch (Exception $e) {
    http_response_code(500);
    echo "ERROR: " . $e->getMessage() . "\n";
    error_log("Web cron error: " . $e->getMessage());
    exit(1);
}
?>

