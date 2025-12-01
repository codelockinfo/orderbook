<?php
/**
 * Test Web Push Library Installation
 * 
 * This script checks if the web-push library is properly installed and can be loaded
 */

echo "==============================================\n";
echo "Web Push Library Test\n";
echo "==============================================\n\n";

// Check autoloader
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
echo "1. Checking autoloader...\n";
if (file_exists($autoloadPath)) {
    echo "   ✅ Autoloader found: {$autoloadPath}\n";
    require_once $autoloadPath;
    echo "   ✅ Autoloader loaded\n";
} else {
    echo "   ❌ Autoloader NOT found: {$autoloadPath}\n";
    echo "   Run: composer install\n";
    exit(1);
}

echo "\n";

// Check if class exists
echo "2. Checking WebPush class...\n";
if (class_exists('Minishlink\WebPush\WebPush')) {
    echo "   ✅ WebPush class found\n";
} else {
    echo "   ❌ WebPush class NOT found\n";
    echo "   The library may not be installed correctly.\n";
    echo "   Try running: composer require minishlink/web-push\n";
    echo "   Or: composer dump-autoload\n";
    exit(1);
}

echo "\n";

// Check VAPID constants
echo "3. Checking VAPID configuration...\n";
require_once __DIR__ . '/../config/config.php';

if (defined('VAPID_SUBJECT')) {
    echo "   ✅ VAPID_SUBJECT: " . substr(VAPID_SUBJECT, 0, 50) . "...\n";
} else {
    echo "   ❌ VAPID_SUBJECT not defined\n";
}

if (defined('VAPID_PUBLIC_KEY')) {
    echo "   ✅ VAPID_PUBLIC_KEY: " . substr(VAPID_PUBLIC_KEY, 0, 20) . "...\n";
} else {
    echo "   ❌ VAPID_PUBLIC_KEY not defined\n";
}

if (defined('VAPID_PRIVATE_KEY')) {
    echo "   ✅ VAPID_PRIVATE_KEY: " . substr(VAPID_PRIVATE_KEY, 0, 20) . "...\n";
} else {
    echo "   ❌ VAPID_PRIVATE_KEY not defined\n";
}

echo "\n";

// Try to instantiate
echo "4. Testing WebPush instantiation...\n";
try {
    if (defined('VAPID_SUBJECT') && defined('VAPID_PUBLIC_KEY') && defined('VAPID_PRIVATE_KEY')) {
        $auth = [
            'VAPID' => [
                'subject' => VAPID_SUBJECT,
                'publicKey' => VAPID_PUBLIC_KEY,
                'privateKey' => VAPID_PRIVATE_KEY,
            ],
        ];
        
        $webPush = new \Minishlink\WebPush\WebPush($auth);
        echo "   ✅ WebPush instantiated successfully\n";
    } else {
        echo "   ⚠️  Cannot test instantiation - VAPID keys missing\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error instantiating WebPush: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";
echo "==============================================\n";
echo "✅ All checks passed! Web Push library is ready.\n";
echo "==============================================\n";
?>

