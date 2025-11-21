<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$userId = getCurrentUserId();
$today = date('Y-m-d');

require_once __DIR__ . '/config/database.php';
$database = new Database();
$db = $database->connect();

// Get today's orders
$stmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? AND order_date = ? AND is_deleted = 0");
$stmt->execute([$userId, $today]);
$todaysOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get tomorrow's orders
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$stmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? AND order_date = ? AND is_deleted = 0");
$stmt->execute([$userId, $tomorrow]);
$tomorrowOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's subscriptions
$stmt = $db->prepare("SELECT * FROM push_subscriptions WHERE user_id = ?");
$stmt->execute([$userId]);
$subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get notification logs
try {
    $stmt = $db->prepare("SELECT * FROM notification_logs WHERE user_id = ? ORDER BY sent_at DESC LIMIT 5");
    $stmt->execute([$userId]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table might not exist yet
    $logs = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Diagnostics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style2.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="header-content">
                <h1><i class="fas fa-stethoscope"></i> Notification Diagnostics</h1>
                <button onclick="window.location.href='index.php'" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
            </div>
        </header>
        
        <!-- Quick Fix Banner -->
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 15px; margin-bottom: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 10px 0; font-size: 18px;">
                <i class="fas fa-exclamation-circle"></i> Not seeing notifications? Here's the quick fix:
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                <div style="background: rgba(255,255,255,0.2); padding: 12px; border-radius: 8px;">
                    <strong>1️⃣ Turn OFF Focus Assist</strong><br>
                    <small>Click taskbar notification icon</small>
                </div>
                <div style="background: rgba(255,255,255,0.2); padding: 12px; border-radius: 8px;">
                    <strong>2️⃣ Check Windows Settings</strong><br>
                    <small>Win + I → System → Notifications</small>
                </div>
                <div style="background: rgba(255,255,255,0.2); padding: 12px; border-radius: 8px;">
                    <strong>3️⃣ Browser Permission</strong><br>
                    <small>Click lock icon in URL bar</small>
                </div>
                <div style="background: rgba(255,255,255,0.2); padding: 12px; border-radius: 8px;">
                    <strong>4️⃣ Check Action Center</strong><br>
                    <small>Press Win + A to see hidden notifications</small>
                </div>
            </div>
        </div>
        
        <main>
            <!-- Browser Status -->
            <div class="filters-section">
                <h3><i class="fas fa-browser"></i> Browser Status</h3>
                <div id="browserStatus"></div>
            </div>
            
            <!-- Database Status -->
            <div class="filters-section" style="margin-top: 20px;">
                <h3><i class="fas fa-database"></i> Database Status</h3>
                
                <div style="padding: 15px; background: #f8f9fa; border-radius: 10px; margin-bottom: 15px;">
                    <strong>Today's Date:</strong> <?php echo $today; ?><br>
                    <strong>User ID:</strong> <?php echo $userId; ?><br>
                    <strong>Username:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?>
                </div>
                
                <div style="padding: 15px; background: <?php echo count($todaysOrders) > 0 ? '#d4edda' : '#fff3cd'; ?>; border-radius: 10px; margin-bottom: 15px;">
                    <strong>📅 Today's Orders:</strong> <?php echo count($todaysOrders); ?> found
                    <?php if (count($todaysOrders) > 0): ?>
                        <ul style="margin-top: 10px;">
                            <?php foreach ($todaysOrders as $order): ?>
                                <li>
                                    Order #<?php echo htmlspecialchars($order['order_number']); ?> 
                                    - <?php echo htmlspecialchars($order['order_time']); ?>
                                    - Status: <?php echo htmlspecialchars($order['status']); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p style="margin-top: 10px; color: #856404;">
                            ⚠️ No orders found for today. Make sure your order date is set to <?php echo $today; ?>
                        </p>
                    <?php endif; ?>
                </div>
                
                <div style="padding: 15px; background: <?php echo count($tomorrowOrders) > 0 ? '#d1ecf1' : '#f8f9fa'; ?>; border-radius: 10px; margin-bottom: 15px;">
                    <strong>📆 Tomorrow's Orders (3x Notification System):</strong> <?php echo count($tomorrowOrders); ?> found
                    <?php if (count($tomorrowOrders) > 0): ?>
                        <ul style="margin-top: 10px;">
                            <?php foreach ($tomorrowOrders as $order): ?>
                                <li>
                                    Order #<?php echo htmlspecialchars($order['order_number']); ?> 
                                    - <?php echo htmlspecialchars($order['order_time']); ?>
                                    <br>
                                    <small style="color: #0c5460;">
                                        Reminders: 
                                        <?php if (isset($order['notification_1_sent'])): ?>
                                            <span style="<?php echo $order['notification_1_sent'] ? 'color: #28a745;' : ''; ?>">
                                                🌅 Morning (<?php echo $order['notification_1_sent'] ? '✓ Sent' : 'Pending'; ?>)
                                            </span>
                                            | 
                                            <span style="<?php echo $order['notification_2_sent'] ? 'color: #28a745;' : ''; ?>">
                                                ☀️ Afternoon (<?php echo $order['notification_2_sent'] ? '✓ Sent' : 'Pending'; ?>)
                                            </span>
                                            | 
                                            <span style="<?php echo $order['notification_3_sent'] ? 'color: #28a745;' : ''; ?>">
                                                🌙 Evening (<?php echo $order['notification_3_sent'] ? '✓ Sent' : 'Pending'; ?>)
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #856404;">⚠️ Run setup-3x-notifications.bat to enable 3x notifications</span>
                                        <?php endif; ?>
                                    </small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p style="margin-top: 10px; color: #0c5460;">
                            ℹ️ No orders found for tomorrow (<?php echo $tomorrow; ?>). Create one to test the 3x notification system!
                        </p>
                    <?php endif; ?>
                </div>
                
                <div style="padding: 15px; background: <?php echo count($subscriptions) > 0 ? '#d4edda' : '#f8d7da'; ?>; border-radius: 10px; margin-bottom: 15px;">
                    <strong>🔔 Push Subscriptions:</strong> <?php echo count($subscriptions); ?> active
                    <?php if (count($subscriptions) == 0): ?>
                        <p style="margin-top: 10px; color: #721c24;">
                            ❌ No subscriptions found! You need to enable notifications first.
                        </p>
                    <?php endif; ?>
                </div>
                
                <div style="padding: 15px; background: #f8f9fa; border-radius: 10px;">
                    <strong>📝 Recent Notification Logs:</strong>
                    <?php if (count($logs) > 0): ?>
                        <ul style="margin-top: 10px;">
                            <?php foreach ($logs as $log): ?>
                                <li>
                                    #<?php echo htmlspecialchars($log['id']); ?>
                                    <?php if (isset($log['sent_at'])): ?>
                                        - <?php echo htmlspecialchars($log['sent_at']); ?>
                                    <?php endif; ?>
                                    - <?php echo htmlspecialchars($log['notification_type'] ?? 'N/A'); ?>
                                    - <?php echo htmlspecialchars($log['status'] ?? 'N/A'); ?>
                                    <br><small><?php echo htmlspecialchars($log['message'] ?? 'No message'); ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p style="margin-top: 10px;">No logs found</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="filters-section" style="margin-top: 20px;">
                <h3><i class="fas fa-tools"></i> Quick Actions</h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                    <button onclick="testBrowserNotification()" class="btn btn-primary">
                        <i class="fas fa-bell"></i> Test Browser Notification
                    </button>
                    
                    <button onclick="enableNotifications()" class="btn btn-success">
                        <i class="fas fa-toggle-on"></i> Enable Notifications
                    </button>
                    
                    <button onclick="sendManualNotification()" class="btn btn-warning">
                        <i class="fas fa-paper-plane"></i> Send Manual Notification
                    </button>
                    
                    <button onclick="send3xNotification()" class="btn btn-success" title="Send 3x daily notification for tomorrow's orders">
                        <i class="fas fa-bell-on"></i> Test 3x Notification
                    </button>
                    
                    <button onclick="testServiceWorkerNotification()" class="btn btn-info" title="Test notification via Service Worker">
                        <i class="fas fa-cog"></i> Test via Service Worker
                    </button>
                    
                    <button onclick="checkWindowsSettings()" class="btn btn-secondary">
                        <i class="fas fa-windows"></i> Check Windows Settings
                    </button>
                    
                    <button onclick="aggressiveNotificationTest()" class="btn btn-warning" title="Sends 3 notifications with different methods">
                        <i class="fas fa-exclamation-triangle"></i> Aggressive Test (3x)
                    </button>
                    
                    <button onclick="window.location.href='test-notification-simple.html'" class="btn btn-secondary">
                        <i class="fas fa-external-link-alt"></i> Open Simple Test
                    </button>
                </div>
                
                <div id="actionResult"></div>
            </div>
            
            <!-- Fix Steps -->
            <div class="table-container" style="margin-top: 20px;">
                <h3><i class="fas fa-wrench"></i> Troubleshooting Steps</h3>
                
                <div style="padding: 20px;">
                    <ol style="line-height: 2.5;">
                        <li>
                            <strong>✅ Enable Browser Permission:</strong> 
                            Click "Enable Notifications" button above and allow when prompted.
                        </li>
                        <li>
                            <strong>🔔 Test Notification:</strong> 
                            Click "Test Browser Notification". If you see the success message but NO notification on screen, continue to step 3.
                        </li>
                        <li>
                            <strong>🪟 Check Windows Focus Assist:</strong>
                            <ul style="margin-top: 10px; line-height: 2;">
                                <li>Click the notification icon in Windows taskbar (bottom-right)</li>
                                <li>Make sure "Focus Assist" is set to OFF</li>
                                <li>If it's on "Priority only" or "Alarms only", your notifications are being blocked</li>
                            </ul>
                        </li>
                        <li>
                            <strong>⚙️ Windows Notification Settings:</strong>
                            <ul style="margin-top: 10px; line-height: 2;">
                                <li>Press <kbd>Win + I</kbd> to open Settings</li>
                                <li>Go to System → Notifications</li>
                                <li>Ensure "Notifications" toggle is ON</li>
                                <li>Scroll down and find your browser (Chrome/Firefox/Edge)</li>
                                <li>Make sure it's enabled and set to show notification banners</li>
                            </ul>
                        </li>
                        <li>
                            <strong>🌐 Browser Settings:</strong>
                            <ul style="margin-top: 10px; line-height: 2;">
                                <li><strong>Chrome:</strong> Click lock icon → Site settings → Notifications → Allow</li>
                                <li><strong>Firefox:</strong> Click lock icon → Permissions → Notifications → Allow</li>
                                <li><strong>Edge:</strong> Click lock icon → Permissions for this site → Notifications → Allow</li>
                            </ul>
                        </li>
                        <li>
                            <strong>🔄 Alternative Test:</strong> 
                            Try "Test via Service Worker" button. If this works but direct notifications don't, there's a browser API restriction.
                        </li>
                        <li>
                            <strong>📅 Verify Order Date:</strong> 
                            Make sure your order is scheduled for TODAY (<?php echo $today; ?>). 
                            <?php if (count($todaysOrders) == 0): ?>
                                <span style="color: #dc3545;">⚠️ Currently no orders found for today!</span>
                            <?php endif; ?>
                        </li>
                    </ol>
                    
                    <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 5px;">
                        <strong>💡 Pro Tip:</strong> Press <kbd>Win + A</kbd> to open Windows Action Center. 
                        Any notifications that were blocked may appear there!
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="assets/js/notifications5.js"></script>
    <script>
        // Check browser status
        function updateBrowserStatus() {
            const browserStatus = document.getElementById('browserStatus');
            
            const supported = 'Notification' in window;
            const permission = supported ? Notification.permission : 'unsupported';
            const swSupported = 'serviceWorker' in navigator;
            const pushSupported = 'PushManager' in window;
            
            let html = '<div style="display: grid; gap: 10px; padding: 15px;">';
            
            html += `<div style="padding: 10px; background: ${supported ? '#d4edda' : '#f8d7da'}; border-radius: 5px;">
                <strong>Notifications Supported:</strong> ${supported ? '✅ Yes' : '❌ No'}
            </div>`;
            
            html += `<div style="padding: 10px; background: ${permission === 'granted' ? '#d4edda' : permission === 'denied' ? '#f8d7da' : '#fff3cd'}; border-radius: 5px;">
                <strong>Permission Status:</strong> ${permission}
                ${permission === 'denied' ? '<br><small>⚠️ You need to enable this in browser settings</small>' : ''}
                ${permission === 'default' ? '<br><small>ℹ️ Click "Enable Notifications" button below</small>' : ''}
            </div>`;
            
            html += `<div style="padding: 10px; background: ${swSupported ? '#d4edda' : '#f8d7da'}; border-radius: 5px;">
                <strong>Service Worker:</strong> ${swSupported ? '✅ Supported' : '❌ Not Supported'}
            </div>`;
            
            html += `<div style="padding: 10px; background: ${pushSupported ? '#d4edda' : '#f8d7da'}; border-radius: 5px;">
                <strong>Push Manager:</strong> ${pushSupported ? '✅ Supported' : '❌ Not Supported'}
            </div>`;
            
            html += '</div>';
            
            browserStatus.innerHTML = html;
        }
        
        function showResult(message, type = 'info') {
            const resultDiv = document.getElementById('actionResult');
            const colors = {
                success: { bg: '#d4edda', text: '#155724' },
                error: { bg: '#f8d7da', text: '#721c24' },
                warning: { bg: '#fff3cd', text: '#856404' },
                info: { bg: '#d1ecf1', text: '#0c5460' }
            };
            const color = colors[type] || colors.info;
            
            resultDiv.innerHTML = `
                <div style="padding: 15px; background: ${color.bg}; color: ${color.text}; border-radius: 10px; margin-top: 15px;">
                    ${message}
                </div>
            `;
        }
        
        function testBrowserNotification() {
            if (!('Notification' in window)) {
                showResult('❌ Your browser does not support notifications', 'error');
                return;
            }
            
            if (Notification.permission === 'granted') {
                try {
                    // Try multiple notification methods (without icon to avoid errors)
                    const notification = new Notification('🔔 Test Notification', {
                        body: 'If you can see this, notifications are working!',
                        tag: 'test-' + Date.now(),
                        requireInteraction: false,
                        silent: false
                    });
                    
                    notification.onclick = function() {
                        console.log('Notification clicked!');
                        window.focus();
                        notification.close();
                    };
                    
                    notification.onerror = function(error) {
                        console.error('Notification error:', error);
                        showResult('❌ Notification error. Check console for details.', 'error');
                    };
                    
                    notification.onshow = function() {
                        console.log('Notification shown successfully!');
                    };
                    
                    showResult('✅ Test notification sent!<br><br>' +
                        '<strong>If you DON\'T see it:</strong><br>' +
                        '1. Check Windows notification settings (bottom-right corner)<br>' +
                        '2. Look for notification in Windows Action Center (Win+A)<br>' +
                        '3. Check browser notification settings (click lock icon in URL bar)<br>' +
                        '4. Disable Windows Focus Assist (it blocks notifications)<br>' +
                        '5. Make sure browser notifications are enabled at OS level', 'success');
                } catch (error) {
                    console.error('Notification error:', error);
                    showResult('❌ Error creating notification: ' + error.message, 'error');
                }
            } else if (Notification.permission === 'denied') {
                showResult('❌ Notifications are BLOCKED. Please enable them in your browser settings:<br><br>' +
                    '<strong>Chrome:</strong> Click lock icon → Site settings → Notifications → Allow<br>' +
                    '<strong>Firefox:</strong> Click lock icon → Permissions → Notifications → Allow', 'error');
            } else {
                showResult('⚠️ Please click "Enable Notifications" button first', 'warning');
            }
        }
        
        async function enableNotifications() {
            if (!('Notification' in window)) {
                showResult('❌ Notifications not supported in this browser', 'error');
                return;
            }
            
            if (Notification.permission === 'denied') {
                showResult('❌ Notifications are BLOCKED. You must enable them manually in browser settings.', 'error');
                return;
            }
            
            try {
                const permission = await Notification.requestPermission();
                
                if (permission === 'granted') {
                    // Subscribe to push
                    await notificationManager.subscribeToPush();
                    showResult('✅ Notifications enabled successfully! Now try "Send Manual Notification"', 'success');
                    updateBrowserStatus();
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showResult('❌ Permission denied', 'error');
                }
            } catch (error) {
                showResult('❌ Error: ' + error.message, 'error');
            }
        }
        
        async function sendManualNotification() {
            try {
                const response = await fetch('api/send-test-notification.php');
                const data = await response.json();
                
                if (data.success) {
                    let message = `✅ ${data.message}<br><br>`;
                    if (data.orders && data.orders.length > 0) {
                        message += '<strong>Orders:</strong><br>';
                        data.orders.forEach(order => {
                            message += `- #${order.orderNumber} at ${order.orderTime} (${order.timeUntil})<br>`;
                        });
                    }
                    showResult(message, 'success');
                    
                    // Also send browser notification
                    if (Notification.permission === 'granted' && data.orders && data.orders.length > 0) {
                        const order = data.orders[0];
                        new Notification('🔔 Order Due Today!', {
                            body: `Your order #${order.orderNumber} is scheduled for today at ${order.orderTime}`,
                            tag: 'order-today'
                        });
                    }
                } else {
                    showResult('⚠️ ' + data.message, 'warning');
                }
            } catch (error) {
                showResult('❌ Error: ' + error.message, 'error');
            }
        }
        
        async function send3xNotification() {
            try {
                const response = await fetch('api/send-3x-test-notification.php');
                const data = await response.json();
                
                if (data.success) {
                    const emoji = data.reminderNumber === 1 ? '🌅' : data.reminderNumber === 2 ? '☀️' : '🌙';
                    let message = `${emoji} ${data.message}<br><br>`;
                    
                    if (data.schedule) {
                        message += '<strong>📋 3x Notification Schedule:</strong><br>';
                        message += '🌅 Morning (8 AM - 1 PM) - Reminder #1<br>';
                        message += '☀️ Afternoon (1 PM - 7 PM) - Reminder #2<br>';
                        message += '🌙 Evening (7 PM - 11 PM) - Reminder #3<br><br>';
                    }
                    
                    if (data.orders && data.orders.length > 0) {
                        message += '<strong>Tomorrow\'s Orders:</strong><br>';
                        data.orders.forEach(order => {
                            message += `- #${order.orderNumber} at ${order.orderTime} on ${order.orderDate}<br>`;
                        });
                    }
                    
                    showResult(message, 'success');
                    
                    // Also send browser notification
                    if (Notification.permission === 'granted' && data.orders && data.orders.length > 0) {
                        const order = data.orders[0];
                        new Notification(`${emoji} ${data.reminderPeriod} Reminder #${data.reminderNumber}`, {
                            body: `${data.reminderText}\nOrder #${order.orderNumber} at ${order.orderTime}`,
                            tag: `3x-reminder-${data.reminderNumber}`,
                            requireInteraction: false
                        });
                    }
                    
                    // Reload page to show updated status
                    setTimeout(() => location.reload(), 2000);
                    
                } else {
                    showResult('⚠️ ' + data.message + (data.hint ? '<br><small>' + data.hint + '</small>' : ''), 'warning');
                }
            } catch (error) {
                showResult('❌ Error: ' + error.message, 'error');
            }
        }
        
        async function testServiceWorkerNotification() {
            try {
                if (!('serviceWorker' in navigator)) {
                    showResult('❌ Service Worker not supported', 'error');
                    return;
                }
                
                const registration = await navigator.serviceWorker.ready;
                
                await registration.showNotification('🔧 Service Worker Test', {
                    body: 'This notification is sent via the Service Worker',
                    tag: 'sw-test-' + Date.now(),
                    requireInteraction: false,
                    vibrate: [200, 100, 200]
                });
                
                showResult('✅ Service Worker notification sent!<br><br>' +
                    'If this one works but the direct notification doesn\'t, there may be a browser-specific issue.', 'success');
            } catch (error) {
                console.error('Service Worker notification error:', error);
                showResult('❌ Service Worker error: ' + error.message, 'error');
            }
        }
        
        async function aggressiveNotificationTest() {
            if (Notification.permission !== 'granted') {
                showResult('❌ Please enable notifications first!', 'error');
                return;
            }
            
            showResult('🚀 Sending 3 test notifications... Watch your screen!', 'info');
            
            try {
                // Method 1: Direct notification with timestamp
                setTimeout(() => {
                    new Notification('🔔 Test #1 - Direct', {
                        body: 'Direct Notification API - ' + new Date().toLocaleTimeString(),
                        tag: 'test-1-' + Date.now(),
                        requireInteraction: true
                    });
                    console.log('Notification #1 sent - Direct API');
                }, 500);
                
                // Method 2: Service Worker notification
                setTimeout(async () => {
                    try {
                        const registration = await navigator.serviceWorker.ready;
                        await registration.showNotification('🔧 Test #2 - Service Worker', {
                            body: 'Service Worker API - ' + new Date().toLocaleTimeString(),
                            tag: 'test-2-' + Date.now(),
                            requireInteraction: true
                        });
                        console.log('Notification #2 sent - Service Worker');
                    } catch (e) {
                        console.error('SW notification failed:', e);
                    }
                }, 1500);
                
                // Method 3: Another direct with vibration
                setTimeout(() => {
                    new Notification('🎯 Test #3 - Final', {
                        body: 'If you can\'t see ANY of these 3 notifications, Windows is blocking them! Time: ' + new Date().toLocaleTimeString(),
                        tag: 'test-3-' + Date.now(),
                        requireInteraction: true,
                        vibrate: [200, 100, 200]
                    });
                    console.log('Notification #3 sent - Direct with vibrate');
                }, 2500);
                
                setTimeout(() => {
                    showResult(
                        '✅ <strong>3 notifications sent!</strong><br><br>' +
                        '<div style="background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #dc3545;">' +
                        '<strong>⚠️ IF YOU CANNOT SEE ANY NOTIFICATIONS:</strong><br><br>' +
                        '1. Press <kbd>Win + A</kbd> RIGHT NOW to check Action Center<br>' +
                        '2. Look at your taskbar (bottom-right) - is Focus Assist ON?<br>' +
                        '3. The notifications ARE being sent - Windows is hiding them<br><br>' +
                        '👉 Click "Check Windows Settings" button for detailed fix steps' +
                        '</div>',
                        'success'
                    );
                }, 3000);
                
            } catch (error) {
                showResult('❌ Error: ' + error.message, 'error');
                console.error('Aggressive test error:', error);
            }
        }
        
        function checkWindowsSettings() {
            showResult(
                '<strong style="font-size: 18px;">🚨 YOUR NOTIFICATIONS ARE BEING BLOCKED BY WINDOWS</strong><br><br>' +
                '<div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #ffc107;">' +
                '<strong>🎯 MOST COMMON ISSUE - Do This FIRST:</strong><br><br>' +
                '1️⃣ <strong>Look at your taskbar</strong> (bottom-right corner of screen)<br>' +
                '2️⃣ Find and click the <strong>notification icon</strong> (looks like a speech bubble)<br>' +
                '3️⃣ Check if it says <strong>"Focus assist: Priority only"</strong> or <strong>"Alarms only"</strong><br>' +
                '4️⃣ If yes, click it and change to <strong>"Off"</strong><br><br>' +
                '👉 <strong>Focus Assist BLOCKS all notifications when turned on!</strong><br>' +
                '</div><br>' +
                
                '<strong>📍 Step 2: Press Win + A RIGHT NOW</strong><br>' +
                '• This opens Windows Action Center<br>' +
                '• Your "hidden" notifications should appear there<br>' +
                '• If you see notifications there, it confirms they\'re working but being hidden<br><br>' +
                
                '<strong>⚙️ Step 3: Windows Notification Settings</strong><br>' +
                '1. Press <kbd>Win + I</kbd> to open Settings<br>' +
                '2. Click <strong>System</strong> → <strong>Notifications & actions</strong><br>' +
                '3. Scroll down to <strong>"Get notifications from these senders"</strong><br>' +
                '4. Find your browser in the list (Chrome/Firefox/Edge)<br>' +
                '5. Make sure:<br>' +
                '   • The toggle is <strong>ON</strong><br>' +
                '   • <strong>"Show notification banners"</strong> is checked<br>' +
                '   • <strong>"Show notifications in action center"</strong> is checked<br><br>' +
                
                '<strong>🔄 Step 4: Test Immediately</strong><br>' +
                'After making changes above, click "Test Browser Notification" again and watch your screen!', 
                'info'
            );
        }
        
        // Auto-update on load
        window.addEventListener('load', () => {
            updateBrowserStatus();
        });
    </script>
</body>
</html>

