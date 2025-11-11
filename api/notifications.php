<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$userId = getCurrentUserId();

// Database connection
$database = new Database();
$db = $database->connect();

try {
    switch ($method) {
        case 'POST':
            // Subscribe to push notifications
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['endpoint']) || !isset($data['keys'])) {
                throw new Exception('Invalid subscription data');
            }
            
            $endpoint = $data['endpoint'];
            $p256dh = $data['keys']['p256dh'];
            $auth = $data['keys']['auth'];
            
            // Check if subscription already exists
            $stmt = $db->prepare("SELECT id FROM push_subscriptions WHERE user_id = ? AND endpoint = ?");
            $stmt->execute([$userId, $endpoint]);
            
            if ($stmt->fetch()) {
                // Update existing subscription
                $stmt = $db->prepare("UPDATE push_subscriptions SET p256dh_key = ?, auth_key = ?, updated_at = NOW() WHERE user_id = ? AND endpoint = ?");
                $stmt->execute([$p256dh, $auth, $userId, $endpoint]);
                $message = 'Subscription updated successfully';
            } else {
                // Insert new subscription
                $stmt = $db->prepare("INSERT INTO push_subscriptions (user_id, endpoint, p256dh_key, auth_key) VALUES (?, ?, ?, ?)");
                $stmt->execute([$userId, $endpoint, $p256dh, $auth]);
                $message = 'Subscription created successfully';
            }
            
            echo json_encode([
                'success' => true,
                'message' => $message
            ]);
            break;
            
        case 'DELETE':
            // Unsubscribe from push notifications
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (isset($data['endpoint'])) {
                $endpoint = $data['endpoint'];
                $stmt = $db->prepare("DELETE FROM push_subscriptions WHERE user_id = ? AND endpoint = ?");
                $stmt->execute([$userId, $endpoint]);
            } else {
                // Delete all subscriptions for user
                $stmt = $db->prepare("DELETE FROM push_subscriptions WHERE user_id = ?");
                $stmt->execute([$userId]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Unsubscribed successfully'
            ]);
            break;
            
        case 'GET':
            // Get user's subscription status
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM push_subscriptions WHERE user_id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'subscribed' => $result['count'] > 0,
                'count' => $result['count']
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Method not allowed'
            ]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

