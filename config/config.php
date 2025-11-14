<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('UTC');

// Detect environment (Local vs Live)
$server = $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? '';

$isLocal = (
    $server === 'localhost' ||
    $server === '127.0.0.1' ||
    str_contains($server, '.test') ||
    str_contains($server, '.local')
);

// Auto-set BASE_URL
if ($isLocal) {
    define('BASE_URL', 'http://localhost/orderbook/');
} else {
    // 🔹 Replace with your LIVE URL
    define('BASE_URL', 'https://forestgreen-bison-718478.hostingersite.com/');
}

// Error Reporting
if ($isLocal) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Include database
require_once __DIR__ . '/database.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }
}
?>
