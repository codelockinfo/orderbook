<?php

// Detect environment
$isLocal = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']) 
           || strpos($_SERVER['SERVER_NAME'], '.test') !== false;

// FCM Configuration is now in config.php (using FCM v1 API with service account)
// FCM Configuration (v1 API)
// Using Firebase Service Account JSON for authentication
// Service Account JSON file should be placed in config/ directory
if (!defined('FCM_SERVICE_ACCOUNT_PATH')) {
    define('FCM_SERVICE_ACCOUNT_PATH', __DIR__ . '/firebase-service-account.json');
}

// Firebase Project ID (found in service account JSON or Firebase Console)
if (!defined('FCM_PROJECT_ID')) {
    define('FCM_PROJECT_ID', 'evently-42c58'); // Your Firebase Project ID
}
// Database Configuration Based on Environment
if ($isLocal) {
    // LOCAL Settings
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'orderbook');
} else {
    // LIVE Settings
    define('DB_HOST', 'localhost');
    define('DB_USER', 'u402017191_orderbook');
    define('DB_PASS', '99@Orderbook');
    define('DB_NAME', 'u402017191_orderbook');
}

class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    private $conn;

    public function connect() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                'mysql:host=' . $this->host . ';dbname=' . $this->dbname,
                $this->user,
                $this->pass
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            echo 'Connection Error: ' . $e->getMessage();
        }

        return $this->conn;
    }
}
?>
