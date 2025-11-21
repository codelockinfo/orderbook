# Fix PWA 404 Error When Opening from Home Screen

## Problem
When adding the website to home screen and opening it, you get a 404 error.

## Root Cause
The `manifest.json` file had a hardcoded `start_url: "/orderbook/index.php"` which doesn't work on different server configurations.

## Solution Implemented

### 1. Created Dynamic Manifest (`manifest.php`)
- Generates manifest with correct paths based on `BASE_URL` from config
- Automatically adjusts `start_url` and `scope` for your server
- Works on both localhost and live server

### 2. Updated All References
- Changed `manifest.json` → `manifest.php` in:
  - `index.php`
  - `login.php`
  - `register.php`
  - `groups.php`
  - `sw2.js`

### 3. Added .htaccess Rules
- Redirects `manifest.json` to `manifest.php` (backward compatibility)
- Sets correct content type for manifest
- Adds security headers

### 4. Improved Service Worker
- Better error handling for offline scenarios
- Handles navigation requests properly
- Falls back to cached `index.php` when offline

## Testing Steps

1. **Clear Browser Cache**
   - Chrome: Settings → Privacy → Clear browsing data → Cached images and files
   - Safari: Settings → Safari → Clear History and Website Data

2. **Uninstall Old PWA**
   - Remove from home screen
   - Or: Settings → Apps → Remove

3. **Re-add to Home Screen**
   - Visit your website
   - Add to home screen again
   - Open from home screen
   - Should now work! ✅

## Verify Manifest

Visit: `https://yourdomain.com/manifest.php`

Should show JSON with correct `start_url` matching your server path.

## If Still Getting 404

1. Check `config/config.php` - `BASE_URL` must be correct
2. Check browser console for errors
3. Verify `manifest.php` is accessible
4. Clear service worker cache:
   - Chrome DevTools → Application → Service Workers → Unregister
   - Application → Clear storage → Clear site data

## Files Changed

- ✅ `manifest.php` (new - dynamic manifest)
- ✅ `index.php` (updated manifest link)
- ✅ `login.php` (updated manifest link)
- ✅ `register.php` (updated manifest link)
- ✅ `groups.php` (updated manifest link)
- ✅ `sw2.js` (updated manifest reference + improved fetch handler)
- ✅ `.htaccess` (new - redirects and headers)

