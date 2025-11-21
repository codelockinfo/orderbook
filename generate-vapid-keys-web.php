<?php
/**
 * VAPID Key Generator - Web Version
 * 
 * Generate VAPID keys via web browser
 * Visit: https://yourdomain.com/generate-vapid-keys-web.php
 * 
 * ⚠️ DELETE THIS FILE after generating keys for security!
 */

require_once __DIR__ . '/config/config.php';

// Only allow if logged in (security)
if (!isLoggedIn()) {
    die('Please login first to generate VAPID keys.');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Generate VAPID Keys</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .key-box { background: #f5f5f5; padding: 15px; border-radius: 4px; margin: 10px 0; word-break: break-all; }
        .success { background: #c8e6c9; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .error { background: #ffcdd2; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .warning { background: #fff3cd; padding: 15px; border-radius: 4px; margin: 10px 0; }
        button { background: #1976d2; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #1565c0; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔑 Generate VAPID Keys</h1>
        
        <?php
        // Check if web-push library is available
        $autoloadPath = __DIR__ . '/vendor/autoload.php';
        
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
            
            if (class_exists('Minishlink\WebPush\VAPID')) {
                try {
                    $keys = \Minishlink\WebPush\VAPID::createVapidKeys();
                    
                    echo '<div class="success">';
                    echo '<strong>✅ Keys generated successfully!</strong><br><br>';
                    
                    echo '<strong>PUBLIC KEY (already in config):</strong><br>';
                    echo '<div class="key-box">' . htmlspecialchars($keys['publicKey']) . '</div>';
                    
                    echo '<strong>PRIVATE KEY (needs to be added to config.php):</strong><br>';
                    echo '<div class="key-box" style="background: #ffebee;">' . htmlspecialchars($keys['privateKey']) . '</div>';
                    
                    echo '<div class="warning">';
                    echo '<strong>⚠️ IMPORTANT: Copy the PRIVATE KEY above!</strong><br><br>';
                    echo '<strong>Next Steps:</strong><br>';
                    echo '1. Open <code>config/config.php</code><br>';
                    echo '2. Find line 46 (VAPID_PRIVATE_KEY)<br>';
                    echo '3. Replace the empty string with the PRIVATE KEY above<br>';
                    echo '4. Save the file<br>';
                    echo '5. Test notifications again';
                    echo '</div>';
                    
                    echo '<div class="success">';
                    echo '<strong>Current PUBLIC KEY in config:</strong><br>';
                    echo '<div class="key-box">' . htmlspecialchars(VAPID_PUBLIC_KEY) . '</div>';
                    
                    if ($keys['publicKey'] === VAPID_PUBLIC_KEY) {
                        echo '<strong>✅ Public keys match! Just add the private key.</strong>';
                    } else {
                        echo '<strong>⚠️ Public keys don\'t match. You may need to update both keys.</strong>';
                    }
                    echo '</div>';
                    
                } catch (Exception $e) {
                    echo '<div class="error">';
                    echo '<strong>❌ Error generating keys:</strong><br>';
                    echo htmlspecialchars($e->getMessage());
                    echo '</div>';
                }
            } else {
                echo '<div class="error">';
                echo '<strong>❌ Web Push library class not found</strong><br>';
                echo 'Please install: <code>composer install</code>';
                echo '</div>';
            }
        } else {
            echo '<div class="error">';
            echo '<strong>❌ Web Push library not installed</strong><br>';
            echo 'Please run: <code>composer install</code> on your server';
            echo '</div>';
        }
        ?>
        
        <div class="warning" style="margin-top: 20px;">
            <strong>🔒 Security Note:</strong><br>
            Delete this file (<code>generate-vapid-keys-web.php</code>) after generating keys to prevent unauthorized access.
        </div>
    </div>
</body>
</html>

