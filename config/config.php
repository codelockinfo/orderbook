<?php
// Configure session to last for 1 week (7 days)
// 7 days = 7 * 24 * 60 * 60 = 604800 seconds
$sessionLifetime = 604800; // 7 days in seconds

// Detect environment (Local vs Live) - needed for cookie domain
$server = $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? '';
$isLocal = (
    $server === 'localhost' ||
    $server === '127.0.0.1' ||
    str_contains($server, '.test') ||
    str_contains($server, '.local')
);

// Detect if running in WebView (Flutter, mobile app, etc.)
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isWebView = (
    stripos($userAgent, 'wv') !== false || // Android WebView
    stripos($userAgent, 'WebView') !== false || // Generic WebView
    stripos($userAgent, 'Flutter') !== false || // Flutter WebView
    stripos($userAgent, 'Mobile') !== false && stripos($userAgent, 'Safari') === false // Mobile WebView
);

// Get the domain for cookie setting (important for WebView compatibility)
$host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
$cookieDomain = '';
if (!empty($host) && !$isLocal) {
    // Extract domain without port
    $hostParts = explode(':', $host);
    $cookieDomain = $hostParts[0];
    // Don't set domain for localhost or IP addresses
    if ($cookieDomain === 'localhost' || filter_var($cookieDomain, FILTER_VALIDATE_IP)) {
        $cookieDomain = '';
    }
}

// Determine if we should use secure cookies
$isSecure = isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === '1');
// For WebView, sometimes we need to be more flexible with SameSite
$sameSite = $isWebView ? 'Lax' : 'Lax'; // Both use Lax for same-domain requests

// Set session cookie parameters before starting session
if (session_status() === PHP_SESSION_NONE) {
    // Set session cookie parameters FIRST (before session_start)
    session_set_cookie_params([
        'lifetime' => $sessionLifetime,
        'path' => '/',
        'domain' => $cookieDomain, // Set domain for proper cookie sharing
        'secure' => $isSecure, // Use secure cookies on HTTPS
        'httponly' => true, // Prevent JavaScript access for security
        'samesite' => $sameSite // CSRF protection (Lax works for both WebView and browsers)
    ]);
    
    // Set session garbage collection max lifetime to 7 days
    ini_set('session.gc_maxlifetime', $sessionLifetime);
    
    // Ensure session cookie is persistent (not session-only)
    ini_set('session.cookie_lifetime', $sessionLifetime);
    
    // Start session
    session_start();
    
    // Check if we need to handle domain migration
    // If session exists but doesn't have user_id or domain changed, clear it
    $currentDomain = $cookieDomain ?: $host;
    
    // Check if domain has changed
    if (isset($_SESSION['current_domain']) && $_SESSION['current_domain'] !== $currentDomain) {
        // Domain changed - destroy old session and start fresh
        session_destroy();
        session_start();
        $_SESSION['current_domain'] = $currentDomain;
    } else if (!isset($_SESSION['current_domain'])) {
        // New session - store current domain
        $_SESSION['current_domain'] = $currentDomain;
    }
    
    // If session exists but user_id is not set (orphaned session from old domain), clear it
    if (isset($_SESSION) && !isset($_SESSION['user_id']) && isset($_COOKIE[session_name()])) {
        // This might be an old session from previous domain
        // Clear the session cookie for all possible domains
        $sessionName = session_name();
        $possibleDomains = [
            $cookieDomain,
            '',
            'localhost'
        ];
        
        // Add host-based domains if available
        if (!empty($host)) {
            $hostParts = explode(':', $host);
            $hostDomain = $hostParts[0];
            $possibleDomains[] = $hostDomain;
            $possibleDomains[] = str_replace('www.', '', $hostDomain);
        }
        
        // Remove null and empty duplicates
        $possibleDomains = array_filter(array_unique($possibleDomains), function($d) {
            return $d !== null && $d !== '';
        });
        
        foreach ($possibleDomains as $domain) {
            setcookie($sessionName, '', time() - 3600, '/', $domain);
            setcookie($sessionName, '', time() - 3600, '/', '.' . $domain);
        }
        
        // Destroy and restart session
        session_destroy();
        session_start();
        $_SESSION['current_domain'] = $currentDomain;
    }
    
    // Regenerate session ID periodically for security (every 30 minutes)
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } else {
        $timeSinceRegeneration = time() - $_SESSION['last_regeneration'];
        // Regenerate every 30 minutes (1800 seconds)
        if ($timeSinceRegeneration > 1800) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
}

// Timezone - Set to India Standard Time (IST)
date_default_timezone_set('Asia/Kolkata');

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
