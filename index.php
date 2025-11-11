<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4CAF50">
    <title>Order Book</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="manifest" href="manifest.json">
    <link rel="icon" type="image/png" href="assets/images/icon-192.png">
</head>
<body>
    <div class="container">
        <header>
            <div class="header-content">
                <h1><i class="fas fa-chart-line"></i> Order Book</h1>
                <div class="user-info">
                    <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></strong></span>
                    <div class="button-row">
                        <button id="notificationBtn" class="btn btn-secondary" title="Toggle notifications">
                            <i class="fas fa-bell"></i>
                            <span id="notificationBadge" class="notification-badge" style="display: none; color: #fff;">ON</span>
                        </button>
                        <button id="calendarViewBtn" class="btn btn-secondary"><i class="fas fa-calendar-alt"></i> Calendar</button>
                    </div>
                    <button id="logoutBtn" class="btn btn-secondary"><i class="fas fa-sign-out-alt"></i> Logout</button>
                </div>
            </div>
        </header>
        
        <main>
            <!-- Filters -->
            <div class="filters-section">
                <h3><i class="fas fa-search"></i> Search & Filters</h3>
                
                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Search by order number...">
                </div>
                
                <div class="filter-controls">
                    <input type="date" id="dateFilter" placeholder="mm/dd/yyyy">
                    
                    <select id="statusFilter">
                        <option value="">All Status</option>
                        <option value="Pending">Pending</option>
                        <option value="Processing">Processing</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                    
                    <button id="clearFiltersBtn" class="btn btn-secondary btn-sm" style="display: none;">
                        <i class="fas fa-times"></i> Clear Filters
                    </button>
                </div>
                
                <div class="action-buttons">
                    <button id="deleteSelectedBtn" class="btn btn-danger">
                        <i class="fas fa-trash-alt"></i> Delete Selected <span id="selectedCount" class="badge">0</span>
                    </button>
                    <button id="addOrderBtn" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add Order
                    </button>
                </div>
            </div>
            
            <!-- Orders Table -->
            <div class="table-container">
                <h3><i class="fas fa-clipboard-list"></i> Orders Dashboard</h3>
                <div class="table-wrapper">
                    <table id="ordersTable">
                        <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>#</th>
                            <th>Order Number</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                        <tbody id="ordersTableBody">
                            <!-- Orders will be loaded here -->
                        </tbody>
                    </table>
                </div>
                
                <div id="noOrders" class="no-orders" style="display: none;">
                    <p>No orders found. Click the + button to add your first order.</p>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Order Form Modal -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle"><i class="fas fa-file-invoice"></i> Add Order</h2>
                <span class="close">&times;</span>
            </div>
            <form id="orderForm">
                <input type="hidden" id="orderId">
                
                <div class="form-group">
                    <label for="orderNumber">Order Number *</label>
                    <input type="text" id="orderNumber" name="order_number" required placeholder="e.g., ORD-2024001">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="orderDate">Order Date *</label>
                        <input type="date" id="orderDate" name="order_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="orderTime">Order Time *</label>
                        <input type="time" id="orderTime" name="order_time" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="orderStatus">Status</label>
                    <select id="orderStatus" name="status">
                        <option value="Pending">Pending</option>
                        <option value="Processing">Processing</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Order</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Calendar View Modal -->
    <div id="calendarModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2><i class="fas fa-calendar-check"></i> Order Calendar</h2>
                <span class="close">&times;</span>
            </div>
            <div id="calendarView"></div>
        </div>
    </div>
    
    <!-- View Order Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-info-circle"></i> Order Details</h2>
                <span class="close">&times;</span>
            </div>
            <div id="orderDetails"></div>
        </div>
    </div>
    
    <script src="assets/js/app.js"></script>
    <script src="assets/js/calendar.js"></script>
    <script src="assets/js/notifications.js"></script>
    <script src="assets/js/auto-notifications.js"></script>
    <script>
        // Register service worker for PWA
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js')
                .then(reg => console.log('Service Worker registered'))
                .catch(err => console.log('Service Worker registration failed'));
        }
    </script>
</body>
</html>

