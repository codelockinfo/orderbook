# 🚀 Quick Setup Guide - Order Book

## ⚡ 5-Minute Setup

### Step 1: Place Files
Copy the entire `orderbook` folder to your web server:
```
C:\wamp\www\orderbook\
```

### Step 2: Create Database
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click **"New"** in left sidebar
3. Database name: `orderbook`
4. Click **"Create"**

### Step 3: Import Database
1. Select `orderbook` database
2. Click **"Import"** tab
3. Click **"Choose File"**
4. Select `database.sql`
5. Click **"Go"** at bottom

### Step 4: Generate PWA Icons
1. Open in browser: `http://localhost/orderbook/create-icons.html`
2. Click **"Generate Icons"**
3. Right-click each icon → Save as → Use exact filename
4. Save all icons to `assets/images/` folder

### Step 5: Access Your App
Open: `http://localhost/orderbook/`

**Default Login:**
- Username: `admin`
- Password: `admin123`

---

## 📋 Configuration (Optional)

### Change Database Credentials
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'orderbook');
```

### Change Base URL
Edit `config/config.php`:
```php
define('BASE_URL', 'http://localhost/orderbook/');
```

---

## ✅ Verification Checklist

- [ ] Database created and imported
- [ ] Can access login page
- [ ] Can login with admin credentials
- [ ] Can add new order
- [ ] Can edit order
- [ ] Can delete order
- [ ] Search works
- [ ] Filters work
- [ ] Calendar opens
- [ ] PWA icons created

---

## 🔧 Troubleshooting

### ❌ Blank Page
**Solution:** Enable error reporting in PHP or check Apache error logs

### ❌ Database Connection Error
**Solution:** Verify credentials in `config/database.php`

### ❌ Can't Login
**Solution:** Reimport `database.sql` file

### ❌ PWA Not Installing
**Solution:** Create icon files using `create-icons.html`

### ❌ 404 on Pages
**Solution:** Ensure `.htaccess` is enabled in Apache config

---

## 📱 Installing as Mobile App

### Android (Chrome):
1. Open app in Chrome
2. Tap menu (⋮)
3. Tap "Install app" or "Add to Home screen"

### iOS (Safari):
1. Open app in Safari
2. Tap Share button
3. Tap "Add to Home Screen"

---

## 🎯 Quick Feature Guide

### Add Order
Click the green **+** button

### Edit Order
Click the ✏️ icon on any row

### Delete Order
Click the 🗑️ icon on any row

### View Details
Click the 👁️ icon on any row

### Bulk Delete
1. Check multiple orders
2. Click red trash button

### Search
Type 3+ characters in search box

### Filter by Date
Click date field and select date

### Filter by Status
Use status dropdown

### Calendar View
Click **📅 Calendar** button

---

## 📁 Project Structure

```
orderbook/
├── 📁 api/
│   ├── auth.php           # Authentication API
│   └── orders.php         # Orders CRUD API
├── 📁 assets/
│   ├── 📁 css/
│   │   └── style.css      # Main stylesheet
│   ├── 📁 js/
│   │   ├── app.js         # Order management
│   │   ├── auth.js        # Login/Register
│   │   └── calendar.js    # Calendar view
│   └── 📁 images/         # PWA icons (create these!)
├── 📁 config/
│   ├── config.php         # App configuration
│   └── database.php       # Database connection
├── 📄 database.sql        # Database schema
├── 📄 index.php           # Main dashboard
├── 📄 login.php           # Login page
├── 📄 register.php        # Registration page
├── 📄 manifest.json       # PWA manifest
├── 📄 sw.js              # Service worker
├── 📄 .htaccess          # Apache config
└── 📄 README.md          # Full documentation
```

---

## 🔐 Security Notes

- Change default admin password immediately
- Use HTTPS in production
- Keep PHP updated
- Regular database backups
- Don't expose config files

---

## 🎨 Customization

### Change Theme Colors
Edit `assets/css/style.css`:
```css
/* Primary color */
background: #4CAF50;  /* Change this */

/* Gradient */
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
```

### Change App Name
Edit `manifest.json`:
```json
"name": "Your App Name"
```

---

## 📊 Database Schema

### Users Table
- id (Primary Key)
- username (Unique)
- email (Unique)
- password (Hashed)
- created_at
- updated_at

### Orders Table
- id (Primary Key)
- order_number (Unique)
- order_date
- order_time
- client_number
- advance_amount
- remain_amount
- total_amount
- status (Pending/Processing/Completed/Cancelled)
- notes
- user_id (Foreign Key)
- created_at
- updated_at

---

## 🚀 Next Steps

After installation:
1. ✅ Change admin password
2. ✅ Register your own account
3. ✅ Add your first order
4. ✅ Explore calendar view
5. ✅ Install as PWA on mobile
6. ✅ Customize theme colors

---

## 📞 Need Help?

- Read `README.md` for detailed documentation
- Check `install.txt` for installation guide
- Review code comments for technical details

---

**Made with ❤️ for efficient order management**

🌟 Don't forget to create the PWA icons using `create-icons.html`!

