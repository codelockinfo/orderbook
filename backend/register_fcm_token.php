<?php
/**
 * Register FCM Token Endpoint
 * 
 * This endpoint receives FCM tokens from the Flutter app and stores them in the database.
 * 
 * POST Parameters:
 * - token: FCM token (required)
 * - platform: 'android' or 'ios' (optional)
 * - user_id: User ID if available (optional)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Include your existing database configuration
// Adjust the path based on your project structure
// If your config files are in a different location, update these paths
$configPath = __DIR__ . '/../config/config.php';
$dbPath = __DIR__ . '/../config/database.php';

// Try alternative paths if the above don't work
if (!file_exists($configPath)) {
    $configPath = __DIR__ . '/../../config/config.php';
    $dbPath = __DIR__ . '/../../config/database.php';
}

if (file_exists($configPath)) {
    require_once $configPath;
}
if (file_exists($dbPath)) {
    require_once $dbPath;
} else {
    // Fallback: If database.php doesn't exist, create connection directly
    // This uses the same pattern as your database.php
    $isLocal = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1'])
        || strpos($_SERVER['SERVER_NAME'], '.test') !== false;

    if ($isLocal) {
        define('DB_HOST', 'localhost');
        define('DB_USER', 'root');
        define('DB_PASS', '');
        define('DB_NAME', 'orderbook');
    } else {
        define('DB_HOST', 'localhost');
        define('DB_USER', 'u402017191_orderbook');
        define('DB_PASS', '99@Orderbook');
        define('DB_NAME', 'u402017191_orderbook');
    }

    if (!class_exists('Database')) {
        class Database
        {
            private $host = DB_HOST;
            private $user = DB_USER;
            private $pass = DB_PASS;
            private $dbname = DB_NAME;
            private $conn;

            public function connect()
            {
                $this->conn = null;
                try {
                    $this->conn = new PDO(
                        'mysql:host=' . $this->host . ';dbname=' . $this->dbname,
                        $this->user,
                        $this->pass
                    );
                    $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    error_log('Database Connection Error: ' . $e->getMessage());
                }
                return $this->conn;
            }
        }
    }
}

try {
    // Use your existing database connection
    $database = new Database();
    $db = $database->connect();

    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Get POST data
    $token = $_POST['token'] ?? '';
    $platform = $_POST['platform'] ?? 'unknown';
    $userId = $_POST['user_id'] ?? null;

    // Validate token
    if (empty($token)) {
        http_response_code(400);
        echo json_encode(['error' => 'Token is required']);
        exit();
    }

    // Sanitize platform
    $platform = in_array($platform, ['android', 'ios']) ? $platform : 'unknown';

    // Ensure fcm_tokens table exists (create if not exists)
    $db->exec("
        CREATE TABLE IF NOT EXISTS fcm_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            token VARCHAR(512) NOT NULL,
            platform VARCHAR(50) DEFAULT 'unknown',
            user_id INT NULL,
            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_token (token),
            KEY idx_user_id (user_id),
            KEY idx_platform (platform)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Insert or update token in database
    $stmt = $db->prepare("
        INSERT INTO fcm_tokens (token, platform, user_id, added_at, updated_at) 
        VALUES (:token, :platform, :user_id, NOW(), NOW())
        ON DUPLICATE KEY UPDATE 
            platform = :platform2, 
            user_id = :user_id2,
            updated_at = NOW()
    ");

    $stmt->execute([
        ':token' => $token,
        ':platform' => $platform,
        ':user_id' => $userId,
        ':platform2' => $platform,
        ':user_id2' => $userId,
    ]);

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Token registered successfully',
        'token' => substr($token, 0, 20) . '...', // Return partial token for security
    ]);

} catch (PDOException $e) {
    error_log('Database error in register_fcm_token: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => 'Failed to register token'
    ]);
} catch (Exception $e) {
    error_log('Error in register_fcm_token: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => 'An unexpected error occurred'
    ]);
}
?>