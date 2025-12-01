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
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Evently">
    <title>Evently</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style2.css">
    <link rel="manifest" href="manifest.php">
    <link rel="icon" type="image/png" href="assets/images/bookify logo (5).png">
    <!-- iOS icons - prevent black border by using proper sizes -->
    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/bookify logo (5).png">
    <link rel="apple-touch-icon" sizes="152x152" href="assets/images/bookify logo (5).png">
    <link rel="apple-touch-icon" sizes="120x120" href="assets/images/bookify logo (5).png">
    <link rel="apple-touch-icon" sizes="76x76" href="assets/images/bookify logo (5).png">
    <link rel="apple-touch-icon" href="assets/images/bookify logo (5).png">
    <link rel="apple-touch-icon" href="assets/images/bookify logo (5).png">
</head>
<body>
    <div class="container">
        <header>
            <div class="header-content">
                <div class="logo">
                    <img src="assets/images/bookify logo (5).png" alt="Evently" height="50px" width="50px">
                    <h1>Evently</h1>
                </div>
                <div class="user-info">
                    <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></strong></span>
                    <div class="button-row">
                        <button id="notificationBtn" class="btn btn-secondary" title="Toggle notifications">
                            <i class="fas fa-bell"></i>
                            <span id="notificationBadge" class="notification-badge" style="display: none; color: #fff;">ON</span>
                        </button>
                        <button id="calendarViewBtn" class="btn btn-secondary" onclick="
                            var modal = document.getElementById('calendarModal');
                            var view = document.getElementById('calendarView');
                            if(modal && view) {
                                modal.classList.add('show');
                                if(typeof openCalendar === 'function') {
                                    openCalendar();
                                } else if(typeof loadCalendar === 'function') {
                                    loadCalendar();
                                } else {
                                    // Fallback: render empty calendar
                                    view.innerHTML = '<p>Loading calendar...</p>';
                                    setTimeout(function() {
                                        if(typeof loadCalendar === 'function') loadCalendar();
                                    }, 100);
                                }
                            } else {
                                alert('Calendar elements not found');
                            }
                            return false;
                        "><i class="fas fa-calendar-alt"></i> Calendar</button>
                        <a href="groups.php" id="myGroupsLink" class="btn btn-primary" style="position: relative;">
                            <i class="fas fa-users"></i> My Groups
                            <span id="requestCountBadge" class="request-count-badge" style="display: none;">0</span>
                        </a>
                        <button id="logoutBtn" class="btn btn-secondary"><i class="fas fa-sign-out-alt"></i> Logout</button>
                    </div>
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
                    <select id="groupFilter" style="min-width: 200px;">
                        <option value="">All Groups</option>
                        <!-- Groups will be loaded dynamically -->
                    </select>
                    
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
                        <i class="fas fa-trash-alt"></i> Delete <span id="selectedCount" class="badge">0</span>
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
                            <th>Group</th>
                            <th>Added By</th>
                            <th style="text-align: center;">Action</th>
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
                    <label for="orderGroup">Group</label>
                    <select id="orderGroup" name="group_id">
                        <option value="">No Group</option>
                        <!-- Groups will be loaded dynamically -->
                    </select>
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

                <!-- Tags input -->
                <div class="form-group">
                    <label for="orderTags">Tags</label>
                    <div class="tags-input-wrapper">
                        <div class="tag-input-row">
                            <div class="color-picker-wrapper">
                            <input type="color" id="tagColorPicker" value="#4CAF50" title="Select tag color">
                                <label for="tagColorPicker" class="color-picker-label" title="Tag Color" id="colorPickerLabel">
                                    <i class="fas fa-palette"></i>
                                </label>
                            </div>
                            <div id="orderTags" class="tags-input" data-placeholder="Type and press Enter"></div>
                        </div>
                        <div id="tagsDisplay" class="tags-display-container"></div>
                    </div>
                    <small style="color: #777;">Type a tag and press Enter to add it. Click × to remove.</small>
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
    
    <!-- Delete Order Confirmation Modal -->
    <div id="deleteOrderModal" class="modal">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h2 style="color: #c62828;"><i class="fas fa-trash-alt"></i> Delete Order</h2>
                <span class="close" id="closeDeleteOrderModal">&times;</span>
            </div>
            <div style="padding: 20px; text-align: center;">
                <div style="font-size: 64px; color: #ef5350; margin-bottom: 10px;">
                    <i class="fas fa-trash-alt"></i>
                </div>
                <h3 style="color: #333; margin-bottom: 10px; font-size: 20px;">Are you sure?</h3>
                <p id="deleteOrderMessage" style="color: #666; margin-bottom: 10px; line-height: 1.6;">
                    You are about to delete this order.
                </p>
                <p style="color: #f44336; font-weight: 600; margin-top: 15px;">
                    <i class="fas fa-exclamation-circle"></i> This action cannot be undone!
                </p>
                <input type="hidden" id="deleteOrderId">
            </div>
            <div class="modal-footer" style="justify-content: center; gap: 15px; padding: 20px 25px;">
                <button type="button" class="btn btn-secondary" id="cancelDeleteOrderBtn">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteOrderBtn">
                    <i class="fas fa-trash-alt"></i> Delete Order
                </button>
            </div>
        </div>
    </div>
    
    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="modal">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h2 style="color: #c62828;"><i class="fas fa-sign-out-alt"></i> Confirm Logout</h2>
                <span class="close" id="closeLogoutModal">&times;</span>
            </div>
            <div style="padding: 20px; text-align: center;">
                <div style="font-size: 64px; color: #667eea; margin-bottom: 15px;">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <h3 style="color: #333; margin-bottom: 15px; font-size: 20px;">Are you sure you want to logout?</h3>
                <p style="color: #666; margin-bottom: 10px; line-height: 1.6;">
                    You will be redirected to the login page.
                </p>
            </div>
            <div class="modal-footer" style="justify-content: center; gap: 15px; padding: 20px 25px;">
                <button type="button" class="btn btn-secondary" id="cancelLogoutBtn">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary" id="confirmLogoutBtn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </div>
    </div>
    
    <script src="assets/js/app5.js"></script>
    <script src="assets/js/calendar1.js"></script>
    <script src="assets/js/notifications5.js"></script>
    <script src="assets/js/auto-notifications5.js"></script>
    <style>
        .request-count-badge {
            color: white!important;
            position: absolute;
            top: -8px;
            right: -8px;
            background: #6f41a1;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
    </style>
    <script>
        // Load request count badge
        async function loadRequestCount() {
            const requestCountBadge = document.getElementById('requestCountBadge');
            if (!requestCountBadge) return;
            
            try {
                const response = await fetch('api/groups.php?action=my-requests-count');
                const data = await response.json();
                
                if (data.success) {
                    const count = data.count || 0;
                    if (count > 0) {
                        requestCountBadge.textContent = count > 99 ? '99+' : count;
                        requestCountBadge.style.display = 'flex';
                    } else {
                        requestCountBadge.style.display = 'none';
                    }
                }
            } catch (error) {
                console.error('Error loading request count:', error);
            }
        }
        
        // Load count on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadRequestCount();
        });
        
        // Register service worker for PWA and notifications
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw2.js')
                .then(reg => {
                    console.log('Service Worker registered successfully', reg);
                    // Wait for service worker to be ready before initializing notifications
                    return navigator.serviceWorker.ready;
                })
                .then(registration => {
                    console.log('Service Worker ready', registration);
                    // Dispatch event to notify that service worker is ready
                    window.dispatchEvent(new CustomEvent('serviceWorkerReady', { detail: registration }));
                })
                .catch(err => {
                    console.error('Service Worker registration failed:', err);
                    // Still dispatch event so notification manager can handle gracefully
                    window.dispatchEvent(new CustomEvent('serviceWorkerReady', { detail: null }));
                });
        } else {
            console.warn('Service Worker not supported');
            // Dispatch event anyway so notification manager can handle gracefully
            window.dispatchEvent(new CustomEvent('serviceWorkerReady', { detail: null }));
        }
    </script>
</body>
</html>

