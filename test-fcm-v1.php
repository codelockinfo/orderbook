<?php
/**
 * FCM V1 API Test Script
 * 
 * This is a simple test file to verify FCM v1 API is working correctly
 * Based on the recommended setup guide
 * 
 * Usage:
 * 1. Replace "DEVICE_TOKEN_HERE" with an actual FCM token from your app
 * 2. Access this file via browser or command line
 * 3. Check the response to see if notification was sent successfully
 */

// Load Composer autoloader
require __DIR__ . '/vendor/autoload.php';

// Load configuration
require_once __DIR__ . '/config/config.php';

/**
 * Send push notification using FCM v1 API
 * 
 * @param string $deviceToken FCM device token
 * @param string $title Notification title
 * @param string $body Notification body
 * @return array Response from FCM API
 */
function sendPushNotification($deviceToken, $title, $body) {
    // Get project ID from config or use default
    $projectId = defined('FCM_PROJECT_ID') ? FCM_PROJECT_ID : 'evently-42c58';
    
    // Get service account path from config
    $serviceAccountPath = defined('FCM_SERVICE_ACCOUNT_PATH') 
        ? FCM_SERVICE_ACCOUNT_PATH 
        : __DIR__ . '/config/firebase-service-account.json';
    
    // Validate service account file exists
    if (!file_exists($serviceAccountPath)) {
        return [
            'success' => false,
            'error' => "Service account file not found: $serviceAccountPath"
        ];
    }
    
    try {
        // Initialize Google Client
        $client = new Google_Client();
        $client->setAuthConfig($serviceAccountPath);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        
        // Get access token
        $tokenData = $client->fetchAccessTokenWithAssertion();
        
        if (!isset($tokenData['access_token'])) {
            return [
                'success' => false,
                'error' => 'Failed to get access token from service account'
            ];
        }
        
        $accessToken = $tokenData['access_token'];
        
        // Prepare FCM v1 message payload
        $payload = [
            "message" => [
                "token" => $deviceToken,
                "notification" => [
                    "title" => $title,
                    "body" => $body
                ],
                // Add Android-specific configuration
                "android" => [
                    "priority" => "high",
                    "notification" => [
                        "sound" => "default",
                        "click_action" => "FLUTTER_NOTIFICATION_CLICK"
                    ]
                ],
                // Add iOS-specific configuration
                "apns" => [
                    "headers" => [
                        "apns-priority" => "10"
                    ],
                    "payload" => [
                        "aps" => [
                            "sound" => "default",
                            "badge" => 1
                        ]
                    ]
                ]
            ]
        ];
        
        // FCM v1 API endpoint
        $url = "https://fcm.googleapis.com/v1/projects/$projectId/messages:send";
        
        // Send request using cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // Handle cURL errors
        if ($curlError) {
            return [
                'success' => false,
                'error' => "cURL Error: $curlError",
                'http_code' => $httpCode
            ];
        }
        
        // Parse response
        $responseData = json_decode($response, true);
        
        // Check HTTP status code
        if ($httpCode !== 200) {
            $errorMessage = $responseData['error']['message'] ?? "HTTP $httpCode";
            return [
                'success' => false,
                'error' => $errorMessage,
                'http_code' => $httpCode,
                'response' => $responseData
            ];
        }
        
        // Success
        return [
            'success' => true,
            'message_id' => $responseData['name'] ?? null,
            'response' => $responseData,
            'http_code' => $httpCode
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// ============================================
// TEST SECTION
// ============================================

// Set content type for JSON response
header('Content-Type: application/json');

// Get device token from query parameter or use placeholder
$deviceToken = $_GET['token'] ?? 'DEVICE_TOKEN_HERE';
$title = $_GET['title'] ?? 'Hello from Evently!';
$body = $_GET['body'] ?? 'Your FCM V1 API is working 🎉';

// If no token provided, show usage instructions
if ($deviceToken === 'DEVICE_TOKEN_HERE') {
    echo json_encode([
        'success' => false,
        'message' => 'Please provide a device token',
        'usage' => [
            'url' => 'test-fcm-v1.php?token=YOUR_DEVICE_TOKEN',
            'optional_params' => [
                'title' => 'Notification title',
                'body' => 'Notification body'
            ],
            'example' => 'test-fcm-v1.php?token=abc123&title=Test&body=Hello World'
        ],
        'info' => [
            'project_id' => defined('FCM_PROJECT_ID') ? FCM_PROJECT_ID : 'evently-42c58',
            'service_account' => defined('FCM_SERVICE_ACCOUNT_PATH') ? FCM_SERVICE_ACCOUNT_PATH : __DIR__ . '/config/firebase-service-account.json',
            'service_account_exists' => file_exists(defined('FCM_SERVICE_ACCOUNT_PATH') ? FCM_SERVICE_ACCOUNT_PATH : __DIR__ . '/config/firebase-service-account.json')
        ]
    ], JSON_PRETTY_PRINT);
    exit;
}

// Send notification
$result = sendPushNotification($deviceToken, $title, $body);

// Return result
echo json_encode($result, JSON_PRETTY_PRINT);
?>

