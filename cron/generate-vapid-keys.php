<?php
/**
 * VAPID Key Generator
 * 
 * This script generates VAPID keys for Web Push notifications
 * Run once to generate your keys, then save them securely
 */

echo "========================================\n";
echo "VAPID Key Generator\n";
echo "========================================\n\n";

// Check if web-push library is available
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    if (class_exists('Minishlink\WebPush\VAPID')) {
        echo "Using web-push library to generate keys...\n\n";
        
        $keys = \Minishlink\WebPush\VAPID::createVapidKeys();
        
        echo "✓ Keys generated successfully!\n\n";
        echo "PUBLIC KEY (use in frontend):\n";
        echo $keys['publicKey'] . "\n\n";
        echo "PRIVATE KEY (use in backend - keep secret!):\n";
        echo $keys['privateKey'] . "\n\n";
        
        echo "========================================\n";
        echo "NEXT STEPS:\n";
        echo "========================================\n";
        echo "1. Copy the PUBLIC KEY\n";
        echo "2. Update assets/js/notifications.js:\n";
        echo "   Find: this.publicVapidKey = '...'\n";
        echo "   Replace with your PUBLIC KEY\n\n";
        echo "3. Copy the PRIVATE KEY\n";
        echo "4. Update cron/send-notifications-production.php:\n";
        echo "   Find: VAPID_PRIVATE_KEY\n";
        echo "   Replace with your PRIVATE KEY\n\n";
        echo "5. Save both keys securely!\n";
        echo "========================================\n";
        
    } else {
        echo "✗ web-push library class not found\n";
        generateKeysManually();
    }
} else {
    echo "✗ web-push library not installed\n\n";
    echo "Please install it first:\n";
    echo "composer require minishlink/web-push\n\n";
    echo "Or use the manual method below:\n\n";
    generateKeysManually();
}

function generateKeysManually() {
    echo "========================================\n";
    echo "Manual Key Generation\n";
    echo "========================================\n\n";
    
    if (function_exists('openssl_pkey_new')) {
        echo "Using OpenSSL to generate keys...\n\n";
        
        $config = [
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1'
        ];
        
        $privateKey = openssl_pkey_new($config);
        
        if ($privateKey === false) {
            echo "✗ Failed to generate keys\n";
            echo "Error: " . openssl_error_string() . "\n";
            return;
        }
        
        $details = openssl_pkey_get_details($privateKey);
        
        // Export private key
        openssl_pkey_export($privateKey, $privateKeyPEM);
        
        // Get public key
        $publicKeyPEM = $details['key'];
        
        // Convert to base64url
        $publicKeyDER = base64_encode($details['ec']['x'] . $details['ec']['y']);
        
        echo "✓ Keys generated!\n\n";
        echo "Note: You still need to convert these to proper VAPID format.\n";
        echo "It's recommended to use the web-push library instead.\n\n";
        
        echo "Install web-push library:\n";
        echo "composer require minishlink/web-push\n\n";
        
    } else {
        echo "✗ OpenSSL extension not available\n\n";
        echo "Please install OpenSSL PHP extension or use the web-push library:\n";
        echo "composer require minishlink/web-push\n";
    }
}
?>

