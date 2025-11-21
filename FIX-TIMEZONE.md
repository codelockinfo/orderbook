# Fix Timezone Issue - India Time

## Problem
Server timezone is different from India timezone, so notifications aren't sending at the right time.

## Solution Applied
I've updated the timezone to **Asia/Kolkata** (India Standard Time - IST) in:
- `config/config.php`
- `cron/send-3x-daily-notifications.php`
- `cron/auto-trigger.php`

## What Changed

**Before:**
```php
date_default_timezone_set('UTC');
```

**After:**
```php
date_default_timezone_set('Asia/Kolkata');
```

## Test Now

1. **Upload the updated files** to your server
2. **Visit the cron URL:**
   ```
   https://forestgreen-bison-718478.hostingersite.com/cron/auto-trigger.php
   ```

3. **You should now see:**
   - Current time matches India time
   - Notifications send during India time windows:
     - Morning: 8:00 AM - 1:00 PM IST
     - Afternoon: 1:00 PM - 7:00 PM IST
     - Evening: 7:00 PM - 11:00 PM IST

## Verify Timezone

To check what timezone is being used, you can create a test file:

**Create `test-timezone.php`:**
```php
<?php
require_once __DIR__ . '/config/config.php';
echo "Current Timezone: " . date_default_timezone_get() . "\n";
echo "Current Time: " . date('Y-m-d H:i:s') . "\n";
echo "Current Hour: " . date('H') . "\n";
?>
```

Visit: `https://yourdomain.com/test-timezone.php`

It should show:
- Timezone: Asia/Kolkata
- Current Time: Should match India time

## Notification Windows (India Time)

- **Morning**: 8:00 AM - 1:00 PM IST
- **Afternoon**: 1:00 PM - 7:00 PM IST
- **Evening**: 7:00 PM - 11:00 PM IST

## After Fixing

1. Upload updated files
2. Test the cron URL
3. It should now work at 11:44 AM India time!
4. You should receive notifications during the correct hours

