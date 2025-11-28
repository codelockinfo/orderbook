# Deployment Guide for FCM v1 API

## ✅ Pre-Deployment Checklist

### Files to Commit
- ✅ `composer.json` - Package dependencies
- ✅ `composer.lock` - Locked dependency versions (required for deployment)
- ✅ `.gitignore` - Updated to allow `composer.lock`
- ✅ All code files (FCMNotificationSender.php, auto-trigger.php, etc.)

### Files NOT to Commit (Already in .gitignore)
- ❌ `vendor/` - Will be installed on server via `composer install`
- ❌ `config/firebase-service-account.json` - Must be uploaded separately

## 🚀 Deployment Steps

### Step 1: Commit and Push Changes

```bash
git add composer.json composer.lock .gitignore
git commit -m "Add FCM v1 API support with Composer dependencies"
git push origin main
```

### Step 2: Upload Service Account JSON

**IMPORTANT:** The service account JSON file must be uploaded manually to your server:

1. **Via FTP/SFTP:**
   - Upload `config/firebase-service-account.json` to your server
   - Path: `public_html/config/firebase-service-account.json`
   - Set permissions: `644` (readable by web server)

2. **Via cPanel File Manager:**
   - Navigate to `public_html/config/`
   - Upload `firebase-service-account.json`
   - Set permissions to `644`

### Step 3: Verify Deployment

The deployment script should:
1. ✅ Detect `composer.lock` file
2. ✅ Run `composer install` automatically
3. ✅ Install all dependencies including `google/apiclient`

### Step 4: Test FCM v1 API

After deployment, test the FCM v1 API:

```
https://your-domain.com/test-fcm-v1.php?token=YOUR_DEVICE_TOKEN
```

Or check auto-trigger:
```
https://your-domain.com/cron/auto-trigger.php
```

## 🔧 Troubleshooting Deployment Issues

### Issue: "Command unexpectedly terminated without error message"

**Possible Causes:**
1. **Composer not installed on server**
   - Solution: Contact hosting support to install Composer
   - Or use `composer.phar` (upload it to server)

2. **PHP version too old**
   - Required: PHP 7.4 or higher
   - Check: `php -v` on server

3. **Memory limit too low**
   - Required: At least 128MB
   - Check: `php -i | grep memory_limit`

4. **Timeout during composer install**
   - Solution: Increase execution time limit
   - Or run `composer install` manually via SSH

### Manual Installation (If Auto-Deployment Fails)

If the deployment script fails, install dependencies manually:

**Via SSH:**
```bash
cd /home/username/public_html
php composer.phar install --no-dev --optimize-autoloader
```

**Via cPanel Terminal:**
```bash
cd ~/public_html
php composer.phar install
```

### Verify Installation

Check if vendor folder exists:
```bash
ls -la vendor/
```

Check if Google API Client is installed:
```bash
ls -la vendor/google/apiclient/
```

## 📋 Server Requirements

- ✅ PHP 7.4 or higher
- ✅ Composer (or composer.phar)
- ✅ cURL extension enabled
- ✅ OpenSSL extension enabled
- ✅ JSON extension enabled
- ✅ At least 128MB memory limit
- ✅ Write permissions for `vendor/` directory

## 🔒 Security Notes

1. **Service Account JSON:**
   - Never commit to Git (already in .gitignore)
   - Upload separately via FTP/SFTP
   - Set permissions to `644` (not `777`)

2. **Composer Lock:**
   - Should be committed (removed from .gitignore)
   - Ensures consistent dependency versions

3. **Vendor Directory:**
   - Should NOT be committed (in .gitignore)
   - Will be generated on server via `composer install`

## ✅ Post-Deployment Verification

1. **Check Files:**
   - ✅ `vendor/autoload.php` exists
   - ✅ `config/firebase-service-account.json` exists
   - ✅ `composer.lock` exists

2. **Test FCM:**
   - Access `test-fcm-v1.php` with a device token
   - Check `auto-trigger.php` response includes FCM results

3. **Check Logs:**
   - Review server error logs for any issues
   - Check PHP error logs if notifications fail

## 🆘 Support

If deployment continues to fail:
1. Check server error logs
2. Verify PHP version and extensions
3. Try manual `composer install` via SSH
4. Contact hosting support if Composer is not available

