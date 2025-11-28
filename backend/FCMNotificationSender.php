<?php
/**
 * FCM Notification Sender Class (FCM v1 API)
 * 
 * This class handles sending push notifications via Firebase Cloud Messaging v1 API
 * Uses Google Service Account for authentication
 */

class FCMNotificationSender
{
    private $db;
    private $serviceAccountPath;
    private $projectId;
    private $accessToken = null;
    private $tokenExpiry = 0;
    private $invalidTokens = [];

    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     * @param string $serviceAccountPath Path to Firebase service account JSON file
     * @param string $projectId Firebase Project ID
     */
    public function __construct($db, $serviceAccountPath = null, $projectId = null)
    {
        $this->db = $db;
        
        // Use provided path or default from config
        $this->serviceAccountPath = $serviceAccountPath ?? (defined('FCM_SERVICE_ACCOUNT_PATH') ? FCM_SERVICE_ACCOUNT_PATH : __DIR__ . '/../config/firebase-service-account.json');
        $this->projectId = $projectId ?? (defined('FCM_PROJECT_ID') ? FCM_PROJECT_ID : 'evently-42c58');
        
        // Validate service account file exists
        if (!file_exists($this->serviceAccountPath)) {
            throw new Exception("Firebase service account file not found: {$this->serviceAccountPath}");
        }
    }

    /**
     * Get access token using service account
     * 
     * @return string Access token
     */
    private function getAccessToken()
    {
        // Return cached token if still valid (tokens expire in 1 hour)
        if ($this->accessToken && time() < $this->tokenExpiry) {
            return $this->accessToken;
        }

        // Check if Google API Client is available
        if (!class_exists('Google_Client')) {
            throw new Exception("Google API Client not found. Please run: composer install");
        }

        try {
            $client = new Google_Client();
            $client->setAuthConfig($this->serviceAccountPath);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            
            $token = $client->fetchAccessTokenWithAssertion();
            
            if (!isset($token['access_token'])) {
                throw new Exception("Failed to get access token from service account");
            }
            
            $this->accessToken = $token['access_token'];
            // Set expiry to 50 minutes (tokens are valid for 1 hour)
            $this->tokenExpiry = time() + (50 * 60);
            
            return $this->accessToken;
        } catch (Exception $e) {
            throw new Exception("Error getting access token: " . $e->getMessage());
        }
    }

    /**
     * Send notification to all FCM tokens
     * 
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Custom data payload (optional)
     * @param array $options Additional options (optional)
     *   - user_ids: Array of user IDs to filter tokens
     *   - platform: 'android' or 'ios' to filter by platform
     * @return array Result with success count, failure count, and invalid tokens
     */
    public function sendToAll($title, $body, $data = [], $options = [])
    {
        // Get tokens based on filters
        $tokens = $this->getTokens($options);

        if (empty($tokens)) {
            return [
                'success' => false,
                'sent' => 0,
                'failed' => 0,
                'invalid_tokens' => [],
                'message' => 'No tokens found'
            ];
        }

        $totalSuccess = 0;
        $totalFailure = 0;
        $this->invalidTokens = [];

        // FCM v1 API sends one message per token
        foreach ($tokens as $token) {
            $result = $this->sendSingleNotification($token, $title, $body, $data);
            
            if ($result['success']) {
                $totalSuccess++;
            } else {
                $totalFailure++;
                // Check if token is invalid
                if (isset($result['error_code']) && in_array($result['error_code'], ['NOT_FOUND', 'INVALID_ARGUMENT', 'PERMISSION_DENIED'])) {
                    $this->invalidTokens[] = $token;
                }
            }
        }

        // Remove invalid tokens
        if (!empty($this->invalidTokens)) {
            $this->removeInvalidTokens();
        }

        return [
            'success' => $totalSuccess > 0,
            'sent' => $totalSuccess,
            'failed' => $totalFailure,
            'invalid_tokens' => count($this->invalidTokens),
            'message' => "Sent to $totalSuccess tokens, $totalFailure failed"
        ];
    }

    /**
     * Send notification to a single device token
     * 
     * @param string $deviceToken FCM device token
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Custom data payload (optional)
     * @return array Result
     */
    private function sendSingleNotification($deviceToken, $title, $body, $data = [])
    {
        try {
            $accessToken = $this->getAccessToken();
            
            // Prepare message for FCM v1 API
            $message = [
                'token' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
            ];

            // Add data payload if provided
            if (!empty($data)) {
                $message['data'] = [];
                foreach ($data as $key => $value) {
                    // FCM v1 requires data values to be strings
                    $message['data'][$key] = is_string($value) ? $value : json_encode($value);
                }
            }

            // Add Android-specific configuration
            $message['android'] = [
                'priority' => 'high',
                'notification' => [
                    'sound' => 'default',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ],
            ];

            // Add iOS-specific configuration
            $message['apns'] = [
                'headers' => [
                    'apns-priority' => '10',
                ],
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                        'badge' => 1,
                    ],
                ],
            ];

            $postData = ['message' => $message];

            // Send to FCM v1 API
            $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $accessToken",
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                return [
                    'success' => false,
                    'error' => "cURL Error: $curlError"
                ];
            }

            if ($httpCode !== 200) {
                $errorData = json_decode($response, true);
                $errorMessage = $errorData['error']['message'] ?? "HTTP $httpCode";
                $errorCode = $errorData['error']['status'] ?? null;
                
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'error_code' => $errorCode,
                    'http_code' => $httpCode
                ];
            }

            $responseData = json_decode($response, true);
            
            return [
                'success' => true,
                'message_id' => $responseData['name'] ?? null
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send notification to specific user IDs
     * 
     * @param array $userIds Array of user IDs
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Custom data payload (optional)
     * @return array Result
     */
    public function sendToUsers($userIds, $title, $body, $data = [])
    {
        return $this->sendToAll($title, $body, $data, ['user_ids' => $userIds]);
    }

    /**
     * Send notification to specific platform
     * 
     * @param string $platform 'android' or 'ios'
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Custom data payload (optional)
     * @return array Result
     */
    public function sendToPlatform($platform, $title, $body, $data = [])
    {
        return $this->sendToAll($title, $body, $data, ['platform' => $platform]);
    }

    /**
     * Get FCM tokens from database with optional filters
     * 
     * @param array $options Filter options
     * @return array Array of FCM tokens
     */
    private function getTokens($options = [])
    {
        $query = "SELECT token FROM fcm_tokens WHERE token IS NOT NULL AND token != ''";
        $params = [];

        // Filter by user IDs
        if (!empty($options['user_ids']) && is_array($options['user_ids'])) {
            $placeholders = implode(',', array_fill(0, count($options['user_ids']), '?'));
            $query .= " AND user_id IN ($placeholders)";
            $params = array_merge($params, $options['user_ids']);
        }

        // Filter by platform
        if (!empty($options['platform']) && in_array($options['platform'], ['android', 'ios'])) {
            $query .= " AND platform = ?";
            $params[] = $options['platform'];
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Remove invalid tokens from database
     */
    private function removeInvalidTokens()
    {
        if (empty($this->invalidTokens)) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($this->invalidTokens), '?'));
        $stmt = $this->db->prepare("DELETE FROM fcm_tokens WHERE token IN ($placeholders)");
        $stmt->execute($this->invalidTokens);
    }

    /**
     * Get statistics about registered tokens
     * 
     * @return array Statistics
     */
    public function getStats()
    {
        $stats = [
            'total' => 0,
            'android' => 0,
            'ios' => 0,
            'unknown' => 0,
        ];

        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN platform = 'android' THEN 1 ELSE 0 END) as android,
                SUM(CASE WHEN platform = 'ios' THEN 1 ELSE 0 END) as ios,
                SUM(CASE WHEN platform NOT IN ('android', 'ios') THEN 1 ELSE 0 END) as unknown
            FROM fcm_tokens
            WHERE token IS NOT NULL AND token != ''
        ");

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $stats = [
                'total' => (int) $result['total'],
                'android' => (int) $result['android'],
                'ios' => (int) $result['ios'],
                'unknown' => (int) $result['unknown'],
            ];
        }

        return $stats;
    }
}
?>
