# Order Book Management System

A comprehensive order management system built with PHP, MySQL, and Progressive Web App (PWA) features. This application allows users to manage orders with features like search, filtering, calendar view, and mobile support.

## Features

✅ **User Authentication**
- User registration and login
- Secure password hashing
- Session management

✅ **Order Management**
- Create, read, update, and delete orders
- Order details including date, time, client info, and amounts
- Real-time status updates
- Bulk delete functionality

✅ **Search & Filter**
- Search by order number or client number
- Filter by date
- Filter by status (Pending, Processing, Completed, Cancelled)

✅ **Calendar Integration**
- Visual calendar view of orders by date
- Click on dates to view specific orders
- Month navigation

✅ **Progressive Web App (PWA)**
- Install on mobile devices
- Offline support with Service Worker
- Responsive design for mobile and desktop

✅ **Push Notifications** 🔔 NEW!
- Automatic reminders 1 day before order date
- Browser notifications (works even when closed)
- Multi-device support
- Easy toggle on/off
- See: [NOTIFICATION-SETUP-GUIDE.md](NOTIFICATION-SETUP-GUIDE.md)

✅ **Modern UI**
- Clean and intuitive interface
- Modal popups for forms
- Smooth animations and transitions
- Mobile-responsive design

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Modern web browser with PWA support

## Installation

### 1. Clone or Download

Place the project files in your web server directory (e.g., `C:\wamp\www\orderbook\`)

### 2. Create Database

Import the database schema:

```bash
mysql -u root -p < database.sql
```

Or manually create the database:
1. Open phpMyAdmin
2. Create a new database named `orderbook`
3. Import the `database.sql` file

### 3. Configure Database Connection

Edit `config/database.php` if needed to match your database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'orderbook');
```

### 4. Update Base URL

Edit `config/config.php` to set your base URL:

```php
define('BASE_URL', 'http://localhost/orderbook/');
```

### 5. Create Icon Files

Create placeholder icon files in the `assets/images/` directory:
- icon-72.png (72x72)
- icon-96.png (96x96)
- icon-128.png (128x128)
- icon-144.png (144x144)
- icon-152.png (152x152)
- icon-192.png (192x192)
- icon-384.png (384x384)
- icon-512.png (512x512)

You can use any online icon generator or create your own branded icons.

### 6. Update Service Worker Path

If your project is not in the `/orderbook/` directory, update the paths in:
- `sw.js` - Update all URLs to match your installation path
- `manifest.json` - Update the `start_url`

### 7. Set Permissions (Linux/Mac)

```bash
chmod -R 755 orderbook
```

## Default Login

After importing the database, you can login with:
- **Username:** admin
- **Email:** admin@orderbook.com
- **Password:** admin123

**Important:** Change this password after first login!

## Project Structure

```
orderbook/
├── api/
│   ├── auth.php          # Authentication API
│   └── orders.php        # Orders CRUD API
├── assets/
│   ├── css/
│   │   └── style.css     # Main stylesheet
│   ├── js/
│   │   ├── app.js        # Main application JS
│   │   ├── auth.js       # Authentication JS
│   │   └── calendar.js   # Calendar functionality
│   └── images/           # PWA icons
├── config/
│   ├── config.php        # Main configuration
│   └── database.php      # Database connection
├── database.sql          # Database schema
├── index.php             # Main order book page
├── login.php             # Login page
├── register.php          # Registration page
├── manifest.json         # PWA manifest
├── sw.js                 # Service worker
└── README.md             # This file
```

## Usage

### Login/Register
1. Navigate to `http://localhost/orderbook/`
2. You'll be redirected to the login page
3. Register a new account or use the default admin credentials
4. After login, you'll see the order book dashboard

### Add Order
1. Click the green **+** button
2. Fill in the order details:
   - Order Number (required)
   - Client Number (required)
   - Order Date (required)
   - Order Time (required)
   - Total Amount (required)
   - Advance Amount (optional)
   - Status (Pending/Processing/Completed/Cancelled)
   - Notes (optional)
3. Click "Save Order"

### Edit Order
1. Click the **✏️** (pencil) icon on any order row
2. Update the desired fields
3. Click "Save Order"

### View Order Details
Click the **👁️** (eye) icon on any order row to view full details

### Delete Order
Click the **🗑️** (trash) icon on any order row to delete (with confirmation)

### Bulk Delete
1. Select multiple orders using checkboxes
2. Click the red trash button at the top
3. Confirm deletion

### Search & Filter
- **Search:** Type at least 3 characters to search by order number or client number
- **Date Filter:** Select a date to view orders on that date
- **Status Filter:** Select a status to filter orders

### Calendar View
1. Click the **📅 Calendar** button
2. View orders by date in a calendar layout
3. Click on any date to view orders for that day
4. Navigate between months using arrow buttons

### Install as PWA (Mobile)
1. Open the app in a mobile browser
2. Look for "Add to Home Screen" prompt
3. Install the app for offline access

## API Endpoints

### Authentication
- `POST api/auth.php?action=register` - Register new user
- `POST api/auth.php?action=login` - User login
- `POST api/auth.php?action=logout` - User logout
- `GET api/auth.php?action=check` - Check login status

### Orders
- `GET api/orders.php?action=list` - Get all orders (with filters)
- `GET api/orders.php?action=get&id={id}` - Get single order
- `POST api/orders.php?action=create` - Create new order
- `POST api/orders.php?action=update` - Update order
- `POST api/orders.php?action=update-status` - Update order status
- `POST api/orders.php?action=delete` - Delete single order
- `POST api/orders.php?action=delete-multiple` - Delete multiple orders
- `GET api/orders.php?action=calendar` - Get calendar data

## Security Features

- Password hashing using PHP's `password_hash()`
- SQL injection protection using prepared statements
- Session-based authentication
- CSRF protection through same-origin policy
- Input validation and sanitization

## Browser Support

- Chrome/Edge (recommended)
- Firefox
- Safari
- Opera

## Troubleshooting

### Can't login after installation
- Verify database was created correctly
- Check PHP error logs
- Ensure session support is enabled in PHP

### PWA not installing
- Ensure HTTPS is enabled (or localhost)
- Check console for service worker errors
- Verify all icon files exist

### Orders not loading
- Check database connection settings
- Verify user is logged in
- Check browser console for JavaScript errors

### Calendar not showing orders
- Verify orders exist for the selected month
- Check date format in database (YYYY-MM-DD)

## Future Enhancements

- Export orders to PDF/Excel
- Email notifications
- Multi-user roles and permissions
- Order templates
- Payment tracking
- Invoice generation
- Advanced reporting and analytics
- Real-time notifications using WebSocket

## License

This project is open source and available for personal and commercial use.

## Support

For issues and questions, please check the code comments or create an issue in the repository.

---

**Built with ❤️ using PHP, MySQL, and modern web technologies**

