# How to Test Notification Functionality

This guide will help you test and verify that the notification system is working correctly.

## Quick Test Steps

### 1. **Test the Notification Button on Home Page**

1. **Open your browser** and navigate to `http://localhost/orderbook/index.php`
2. **Open Browser Developer Console** (Press `F12` or `Ctrl+Shift+I`)
   - Go to the **Console** tab
   - Look for messages like:
     - "Service Worker registered successfully"
     - "Service Worker ready"
     - "Initializing notification UI..."
     - "Notification button event listener added"

3. **Click the Bell Icon Button** (🔔) in the header
   - You should see a browser permission prompt asking to allow notifications
   - Click **"Allow"** or **"Block"** (choose Allow for testing)

4. **Expected Results:**
   - ✅ A toast notification appears saying "Notifications enabled! You will receive reminders 1 day before orders."
   - ✅ The badge "ON" appears on the bell icon
   - ✅ Console shows: "Push subscription successful"
   - ✅ No JavaScript errors in console

5. **Click the Bell Icon Again** to disable:
   - ✅ Toast shows "Notifications disabled"
   - ✅ Badge disappears
   - ✅ Console shows: "Unsubscribed from push notifications"

### 2. **Check Browser Console for Errors**

Open Developer Tools (F12) and check the Console tab for:
- ❌ **Red errors** - These indicate problems
- ⚠️ **Yellow warnings** - These are usually non-critical
- ✅ **Green/Info messages** - These show successful operations

**Common Issues:**
- `Service Worker registration failed` - Check if `sw.js` file exists
- `Failed to subscribe to push notifications` - Check VAPID key or service worker
- `HTTP error! status: 401` - User not logged in
- `HTTP error! status: 500` - Server-side error (check PHP error logs)

### 3. **Use the Diagnostic Page**

1. Navigate to: `http://localhost/orderbook/diagnose-notifications.php`
2. This page shows:
   - ✅ Browser support status
   - ✅ Permission status
   - ✅ Service Worker status
   - ✅ Subscription status
   - ✅ Your orders (today and tomorrow)

3. **Test Buttons Available:**
   - **"Test Browser Notification"** - Sends a test notification immediately
   - **"Enable Notifications"** - Enables notifications and subscribes
   - **"Send Manual Notification"** - Sends notification for today's orders
   - **"Test 3x Notification"** - Tests the 3x daily notification system
   - **"Test via Service Worker"** - Tests service worker notifications
   - **"Aggressive Test (3x)"** - Sends 3 test notifications using different methods

### 4. **Check Service Worker Status**

1. Open Developer Tools (F12)
2. Go to **Application** tab (Chrome) or **Storage** tab (Firefox)
3. Click on **Service Workers** in the left sidebar
4. You should see:
   - ✅ Status: **activated and is running**
   - ✅ Source: `sw.js`
   - ✅ No errors

### 5. **Check Notification Permission**

**In Browser:**
1. Click the **lock icon** or **info icon** in the address bar
2. Check **Notifications** permission:
   - Should be set to **"Allow"** for testing
   - If "Block", click and change to "Allow"

**In Code (Console):**
```javascript
// Run this in browser console:
console.log('Permission:', Notification.permission);
// Should output: "granted", "denied", or "default"
```

### 6. **Check Subscription Status**

**In Browser Console:**
```javascript
// Check if notification manager is loaded
console.log('Notification Manager:', typeof notificationManager);

// Check subscription status
notificationManager.checkSubscription().then(status => {
    console.log('Subscription Status:', status);
});
```

**Expected Output:**
```javascript
{
    success: true,
    subscribed: true,  // or false if not subscribed
    count: 1  // number of subscriptions
}
```

### 7. **Test Push Notification Reception**

**Method 1: Using Diagnostic Page**
1. Go to `diagnose-notifications.php`
2. Click **"Send Manual Notification"**
3. You should receive a notification if you have orders due today

**Method 2: Using Browser Console**
```javascript
// Test direct notification
if (Notification.permission === 'granted') {
    new Notification('Test Notification', {
        body: 'This is a test notification',
        icon: '/orderbook/assets/images/icon-192.png'
    });
}
```

**Method 3: Test via Service Worker**
```javascript
// In browser console
navigator.serviceWorker.ready.then(registration => {
    registration.showNotification('Test via SW', {
        body: 'This notification came from Service Worker',
        tag: 'test-sw'
    });
});
```

### 8. **Check Database Subscriptions**

**Using PHPMyAdmin or MySQL:**
```sql
-- Check if your subscription is saved
SELECT * FROM push_subscriptions WHERE user_id = YOUR_USER_ID;

-- Check notification logs
SELECT * FROM notification_logs WHERE user_id = YOUR_USER_ID ORDER BY sent_at DESC LIMIT 10;
```

### 9. **Windows-Specific Checks**

If you're on Windows and notifications aren't showing:

1. **Check Focus Assist:**
   - Click the notification icon in system tray (bottom-right)
   - Make sure **Focus Assist** is **OFF**
   - If it's on "Priority only" or "Alarms only", notifications are blocked

2. **Check Windows Notification Settings:**
   - Settings → System → Notifications & actions
   - Make sure browser notifications are enabled
   - Check "Get notifications from apps and other senders" is ON

3. **Check Browser in Windows Settings:**
   - Settings → System → Notifications & actions
   - Scroll to find your browser (Chrome/Edge/Firefox)
   - Make sure it's enabled

### 10. **Common Issues and Solutions**

| Issue | Solution |
|-------|----------|
| Button doesn't respond | Check console for errors, verify `notifications2.js` is loaded |
| Permission prompt doesn't appear | Check if already denied, reset in browser settings |
| Notifications not showing | Check Windows Focus Assist, browser permission, service worker status |
| "Service Worker not supported" | Use Chrome, Edge, or Firefox (not IE) |
| "Failed to subscribe" | Check VAPID key, service worker registration, network connection |
| Badge doesn't appear | Check if `notificationBadge` element exists in HTML |

### 11. **Step-by-Step Complete Test**

1. ✅ **Clear browser cache** (Ctrl+Shift+Delete)
2. ✅ **Open** `http://localhost/orderbook/index.php`
3. ✅ **Open Console** (F12)
4. ✅ **Check for errors** - Should see "Service Worker registered"
5. ✅ **Click bell button** - Should see permission prompt
6. ✅ **Allow notifications** - Should see success toast
7. ✅ **Check badge** - Should see "ON" on bell icon
8. ✅ **Go to diagnostic page** - `diagnose-notifications.php`
9. ✅ **Click "Test Browser Notification"** - Should see notification
10. ✅ **Check console** - Should see "Notification shown successfully!"

### 12. **Automated Testing Script**

You can also test programmatically in the browser console:

```javascript
// Complete test sequence
async function testNotifications() {
    console.log('=== Starting Notification Test ===');
    
    // 1. Check support
    console.log('1. Support:', 'Notification' in window && 'serviceWorker' in navigator);
    
    // 2. Check permission
    console.log('2. Permission:', Notification.permission);
    
    // 3. Check service worker
    if ('serviceWorker' in navigator) {
        const reg = await navigator.serviceWorker.ready;
        console.log('3. Service Worker:', reg ? 'Ready' : 'Not ready');
    }
    
    // 4. Check subscription
    if (typeof notificationManager !== 'undefined') {
        const status = await notificationManager.checkSubscription();
        console.log('4. Subscription:', status);
    }
    
    // 5. Test notification
    if (Notification.permission === 'granted') {
        new Notification('Test', { body: 'If you see this, it works!' });
        console.log('5. Test notification sent');
    }
    
    console.log('=== Test Complete ===');
}

// Run the test
testNotifications();
```

## Success Indicators

✅ **Everything is working if:**
- Bell button responds to clicks
- Permission prompt appears (if not already granted)
- Toast notifications appear
- Badge shows "ON" when enabled
- Test notifications appear on screen
- No errors in console
- Service Worker is active
- Subscription is saved in database

## Need Help?

If notifications still don't work:
1. Check the browser console for specific error messages
2. Verify all files are loaded (Network tab in DevTools)
3. Check PHP error logs in XAMPP
4. Verify database tables exist (`push_subscriptions`, `notification_logs`)
5. Test in a different browser (Chrome recommended)

