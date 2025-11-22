<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone - Set to India Standard Time (IST)
date_default_timezone_set('Asia/Kolkata');

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

// VAPID Keys for Web Push Notifications
// Generate keys using: php cron/generate-vapid-keys.php
if (!defined('VAPID_PUBLIC_KEY')) {
    // Default key - REPLACE with your generated public key
    define('VAPID_PUBLIC_KEY', 'BLQ7g9sVZMubeodFPZowNKKeZT6KcZVOc3YSB8zNjAq6ilV4aM1ljRb3zD9jd4I593IVkWfF1BeeooZAB90-xPk');
}
if (!defined('VAPID_PRIVATE_KEY')) {
    // Default key - REPLACE with your generated private key
    // This should be kept secret and not committed to version control
       define('VAPID_PRIVATE_KEY', 'fLMDUGEeTvzau6oFwa2J4g0PyyARFICdqSqh3t5pTdo');
}
if (!defined('VAPID_SUBJECT')) {
    // Email or URL identifying your application
    define('VAPID_SUBJECT', BASE_URL);
}

// Optional mail configuration (define SMTP_* constants here)
$mailConfigFile = __DIR__ . '/mail.php';
if (file_exists($mailConfigFile)) {
    require_once $mailConfigFile;
}

if (!defined('SMTP_HOST')) {
    define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.hostinger.com');
}

if (!defined('SMTP_PORT')) {
    define('SMTP_PORT', (int)(getenv('SMTP_PORT') ?: 465));
}

if (!defined('SMTP_ENCRYPTION')) {
    define('SMTP_ENCRYPTION', strtolower(getenv('SMTP_ENCRYPTION') ?: 'tls'));
}

if (!defined('SMTP_USERNAME')) {
    define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: 'tailorpro@happyeventsurat.com');
}

if (!defined('SMTP_PASSWORD')) {
    define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: 'Tailor@99');
}

if (!defined('SMTP_FROM_EMAIL')) {
    define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: 'tailorpro@happyeventsurat.com');
}

if (!defined('SMTP_FROM_NAME')) {
    define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Evently');
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
