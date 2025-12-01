<?php
/**
 * Fix VAPID Key Mismatch
 * 
 * This script helps fix the "VAPID credentials do not correspond" error
 * by either:
 * 1. Showing which subscriptions need to be re-subscribed
 * 2. Clearing old subscriptions so users can re-enable notifications
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Ensure timezone is set
if (!ini_get('date.timezone') || ini_get('date.timezone') !== 'Asia/Kolkata') {
    date_default_timezone_set('Asia/Kolkata');
}

try {
    $database = new Database();
    $db = $database->connect();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    echo "==============================================\n";
    echo "VAPID Key Mismatch Fixer\n";
    echo "==============================================\n\n";
    
    // Show current VAPID keys
    echo "Current VAPID Keys in config.php:\n";
    if (defined('VAPID_PUBLIC_KEY')) {
        echo "  Public Key: " . substr(VAPID_PUBLIC_KEY, 0, 30) . "...\n";
    } else {
        echo "  ❌ VAPID_PUBLIC_KEY not defined\n";
    }
    if (defined('VAPID_SUBJECT')) {
        echo "  Subject: " . VAPID_SUBJECT . "\n";
    } else {
        echo "  ❌ VAPID_SUBJECT not defined\n";
    }
    echo "\n";
    
    // Count subscriptions
    $sql = "SELECT COUNT(*) as total FROM push_subscriptions";
    $stmt = $db->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalSubscriptions = $result['total'] ?? 0;
    
    echo "Total subscriptions in database: {$totalSubscriptions}\n\n";
    
    if ($totalSubscriptions == 0) {
        echo "✅ No subscriptions found. Users need to enable notifications.\n";
        exit(0);
    }
    
    // Get subscriptions by user
    $sql = "SELECT ps.user_id, u.username, COUNT(*) as subscription_count
            FROM push_subscriptions ps
            JOIN users u ON ps.user_id = u.id
            GROUP BY ps.user_id, u.username
            ORDER BY subscription_count DESC";
    $stmt = $db->query($sql);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Subscriptions by user:\n";
    foreach ($users as $user) {
        echo "  - {$user['username']} (ID: {$user['user_id']}): {$user['subscription_count']} subscription(s)\n";
    }
    echo "\n";
    
    echo "==============================================\n";
    echo "Solution Options:\n";
    echo "==============================================\n\n";
    
    echo "Option 1: Clear all subscriptions (Recommended)\n";
    echo "  Users will need to re-enable notifications in their browser.\n";
    echo "  This ensures all subscriptions use the current VAPID keys.\n";
    echo "  Run: php fix-vapid-mismatch.php --clear\n\n";
    
    echo "Option 2: Clear subscriptions for specific user\n";
    echo "  Run: php fix-vapid-mismatch.php --clear-user [user_id]\n\n";
    
    echo "Option 3: Regenerate VAPID keys\n";
    echo "  If you want to keep existing subscriptions, you need to:\n";
    echo "  1. Find the old VAPID keys that were used when subscriptions were created\n";
    echo "  2. Update config.php with those keys\n";
    echo "  OR regenerate keys and have users re-subscribe\n\n";
    
    // Handle command line arguments
    if (php_sapi_name() === 'cli') {
        $args = $argv ?? [];
        
        if (in_array('--clear', $args)) {
            echo "Clearing all subscriptions...\n";
            $deleteSql = "DELETE FROM push_subscriptions";
            $deleteStmt = $db->prepare($deleteSql);
            $deleteStmt->execute();
            $deleted = $deleteStmt->rowCount();
            echo "✅ Deleted {$deleted} subscription(s)\n";
            echo "\nUsers will need to re-enable notifications in their browser.\n";
        } elseif (in_array('--clear-user', $args)) {
            $userIdIndex = array_search('--clear-user', $args) + 1;
            if (isset($args[$userIdIndex])) {
                $userId = (int)$args[$userIdIndex];
                echo "Clearing subscriptions for user ID: {$userId}...\n";
                $deleteSql = "DELETE FROM push_subscriptions WHERE user_id = ?";
                $deleteStmt = $db->prepare($deleteSql);
                $deleteStmt->execute([$userId]);
                $deleted = $deleteStmt->rowCount();
                echo "✅ Deleted {$deleted} subscription(s) for user ID {$userId}\n";
            } else {
                echo "❌ Error: Please provide user_id after --clear-user\n";
                echo "Usage: php fix-vapid-mismatch.php --clear-user [user_id]\n";
            }
        } else {
            echo "To clear subscriptions, run with --clear flag:\n";
            echo "  php fix-vapid-mismatch.php --clear\n";
        }
    } else {
        echo "To clear subscriptions, access this script via command line:\n";
        echo "  php cron/fix-vapid-mismatch.php --clear\n";
        echo "\nOr add ?clear=1 to the URL (for web access):\n";
        
        if (isset($_GET['clear']) && $_GET['clear'] == '1') {
            echo "\n⚠️  Clearing all subscriptions...\n";
            $deleteSql = "DELETE FROM push_subscriptions";
            $deleteStmt = $db->prepare($deleteSql);
            $deleteStmt->execute();
            $deleted = $deleteStmt->rowCount();
            echo "✅ Deleted {$deleted} subscription(s)\n";
            echo "\nUsers will need to re-enable notifications in their browser.\n";
        } else {
            echo "  Add ?clear=1 to clear all subscriptions\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>

