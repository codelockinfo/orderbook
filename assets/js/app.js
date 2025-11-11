// Main Application JavaScript

const API_BASE = 'api/';

// State
let orders = [];
let selectedOrders = new Set();
let currentEditOrderId = null;

// DOM Elements
const searchInput = document.getElementById('searchInput');
const dateFilter = document.getElementById('dateFilter');
const statusFilter = document.getElementById('statusFilter');
const clearFiltersBtn = document.getElementById('clearFiltersBtn');
const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
const selectedCountBadge = document.getElementById('selectedCount');
const addOrderBtn = document.getElementById('addOrderBtn');
const selectAllCheckbox = document.getElementById('selectAll');
const ordersTableBody = document.getElementById('ordersTableBody');
const noOrdersDiv = document.getElementById('noOrders');
const logoutBtn = document.getElementById('logoutBtn');

// Modal Elements
const orderModal = document.getElementById('orderModal');
const orderForm = document.getElementById('orderForm');
const modalTitle = document.getElementById('modalTitle');
const cancelBtn = document.getElementById('cancelBtn');
const closeButtons = document.querySelectorAll('.close');

const viewModal = document.getElementById('viewModal');
const orderDetails = document.getElementById('orderDetails');

// Update Clear Filters Button Visibility
function updateClearFiltersButton() {
    const hasFilters = dateFilter.value || statusFilter.value || searchInput.value.trim();
    clearFiltersBtn.style.display = hasFilters ? 'inline-flex' : 'none';
}

// Load Orders
async function loadOrders() {
    const search = searchInput.value.trim();
    const date = dateFilter.value;
    const status = statusFilter.value;
    
    updateClearFiltersButton();
    
    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (date) params.append('date', date);
    if (status) params.append('status', status);
    
    try {
        const response = await fetch(`${API_BASE}orders.php?action=list&${params.toString()}`);
        const data = await response.json();
        
        if (data.success) {
            orders = data.orders;
            renderOrders();
        }
    } catch (error) {
        console.error('Error loading orders:', error);
    }
}

// Render Orders
function renderOrders() {
    ordersTableBody.innerHTML = '';
    
    if (orders.length === 0) {
        noOrdersDiv.style.display = 'block';
        return;
    }
    
    noOrdersDiv.style.display = 'none';
    
    orders.forEach((order, index) => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><input type="checkbox" class="order-checkbox" data-id="${order.id}"></td>
            <td>${index + 1}</td>
            <td><strong>${order.order_number}</strong></td>
            <td>${order.order_date}</td>
            <td>${order.order_time}</td>
            <td>
                <div class="action-cell">
                    <button class="btn btn-danger btn-sm" onclick="deleteOrder(${order.id})"><i class="fas fa-trash-alt"></i></button>
                    <button class="btn btn-secondary btn-sm" onclick="editOrder(${order.id})"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-secondary btn-sm" onclick="viewOrder(${order.id})"><i class="fas fa-eye"></i></button>
                    <select class="status-select" data-status="${order.status}" onchange="updateOrderStatus(${order.id}, this.value, this)">
                        <option value="Pending" ${order.status === 'Pending' ? 'selected' : ''}>Pending</option>
                        <option value="Processing" ${order.status === 'Processing' ? 'selected' : ''}>Processing</option>
                        <option value="Completed" ${order.status === 'Completed' ? 'selected' : ''}>Completed</option>
                        <option value="Cancelled" ${order.status === 'Cancelled' ? 'selected' : ''}>Cancelled</option>
                    </select>
                </div>
            </td>
        `;
        ordersTableBody.appendChild(row);
    });
    
    // Update checkboxes
    updateCheckboxes();
    updateSelectedCount();
}

// Update Checkboxes
function updateCheckboxes() {
    document.querySelectorAll('.order-checkbox').forEach(checkbox => {
        checkbox.checked = selectedOrders.has(parseInt(checkbox.dataset.id));
        // Remove old event listeners by cloning
        const newCheckbox = checkbox.cloneNode(true);
        checkbox.parentNode.replaceChild(newCheckbox, checkbox);
        
        newCheckbox.addEventListener('change', (e) => {
            const id = parseInt(e.target.dataset.id);
            if (e.target.checked) {
                selectedOrders.add(id);
            } else {
                selectedOrders.delete(id);
            }
            updateSelectAllCheckbox();
            updateSelectedCount();
        });
    });
}

// Update Select All Checkbox
function updateSelectAllCheckbox() {
    const checkboxes = document.querySelectorAll('.order-checkbox');
    selectAllCheckbox.checked = checkboxes.length > 0 && selectedOrders.size === checkboxes.length;
}

// Update Selected Count Badge
function updateSelectedCount() {
    const count = selectedOrders.size;
    selectedCountBadge.textContent = count;
    
    // Show badge only when count > 0
    if (count > 0) {
        selectedCountBadge.classList.add('show');
    } else {
        selectedCountBadge.classList.remove('show');
    }
}

// Select All
selectAllCheckbox.addEventListener('change', (e) => {
    const checkboxes = document.querySelectorAll('.order-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = e.target.checked;
        const id = parseInt(checkbox.dataset.id);
        if (e.target.checked) {
            selectedOrders.add(id);
        } else {
            selectedOrders.delete(id);
        }
    });
    updateSelectedCount();
});

// Delete Selected Orders
deleteSelectedBtn.addEventListener('click', async () => {
    if (selectedOrders.size === 0) {
        alert('Please select orders to delete');
        return;
    }
    
    if (!confirm(`Are you sure you want to delete ${selectedOrders.size} order(s)?`)) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}orders.php?action=delete-multiple`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ ids: Array.from(selectedOrders) })
        });
        
        const data = await response.json();
        
        if (data.success) {
            selectedOrders.clear();
            updateSelectedCount();
            loadOrders();
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error('Error deleting orders:', error);
        alert('Failed to delete orders');
    }
});

// Open Add Order Modal
addOrderBtn.addEventListener('click', () => {
    currentEditOrderId = null;
    modalTitle.textContent = 'Add Order';
    orderForm.reset();
    document.getElementById('orderId').value = '';
    
    // Set default date and time
    const now = new Date();
    document.getElementById('orderDate').value = now.toISOString().split('T')[0];
    document.getElementById('orderTime').value = now.toTimeString().slice(0, 5);
    
    orderModal.classList.add('show');
});

// Open Edit Order Modal
async function editOrder(id) {
    currentEditOrderId = id;
    modalTitle.textContent = 'Edit Order';
    
    try {
        const response = await fetch(`${API_BASE}orders.php?action=get&id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const order = data.order;
            document.getElementById('orderId').value = order.id;
            document.getElementById('orderNumber').value = order.order_number;
            document.getElementById('orderDate').value = order.order_date;
            document.getElementById('orderTime').value = order.order_time;
            document.getElementById('orderStatus').value = order.status;
            
            orderModal.classList.add('show');
        }
    } catch (error) {
        console.error('Error loading order:', error);
        alert('Failed to load order details');
    }
}

// View Order
async function viewOrder(id) {
    try {
        const response = await fetch(`${API_BASE}orders.php?action=get&id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const order = data.order;
            orderDetails.innerHTML = `
                <div class="order-details-view">
                    <div class="detail-row">
                        <div class="detail-label">Order Number:</div>
                        <div class="detail-value"><strong>${order.order_number}</strong></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Date:</div>
                        <div class="detail-value">${order.order_date}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Time:</div>
                        <div class="detail-value">${order.order_time}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Status:</div>
                        <div class="detail-value"><span class="status-badge status-${order.status}">${order.status}</span></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Created:</div>
                        <div class="detail-value">${order.created_at || 'N/A'}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Last Updated:</div>
                        <div class="detail-value">${order.updated_at || 'N/A'}</div>
                    </div>
                </div>
            `;
            viewModal.classList.add('show');
        }
    } catch (error) {
        console.error('Error loading order:', error);
        alert('Failed to load order details');
    }
}

// Save Order (Create/Update)
orderForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = {
        order_number: document.getElementById('orderNumber').value,
        order_date: document.getElementById('orderDate').value,
        order_time: document.getElementById('orderTime').value,
        status: document.getElementById('orderStatus').value
    };
    
    if (currentEditOrderId) {
        formData.id = currentEditOrderId;
    }
    
    const action = currentEditOrderId ? 'update' : 'create';
    
    try {
        const response = await fetch(`${API_BASE}orders.php?action=${action}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            orderModal.classList.remove('show');
            loadOrders();
            
            // Show notification message if available
            if (data.notification) {
                if (data.notification.sent) {
                    showNotificationToast(data.notification.message, 'success');
                    
                    // Also send browser notification if permission granted
                    if (Notification.permission === 'granted') {
                        new Notification('🔔 Auto Notification Sent!', {
                            body: data.notification.message,
                            tag: 'order-auto-notification'
                        });
                    }
                } else if (data.notification.scheduled) {
                    showNotificationToast(data.notification.message, 'info');
                }
            } else {
                // Default success message if no notification info
                showNotificationToast(data.message, 'success');
            }
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error('Error saving order:', error);
        alert('Failed to save order');
    }
});

// Delete Order
async function deleteOrder(id) {
    if (!confirm('Are you sure you want to delete this order?')) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}orders.php?action=delete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id })
        });
        
        const data = await response.json();
        
        if (data.success) {
            loadOrders();
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error('Error deleting order:', error);
        alert('Failed to delete order');
    }
}

// Update Order Status
async function updateOrderStatus(id, status, selectElement) {
    // Update the data-status attribute immediately for visual feedback
    selectElement.setAttribute('data-status', status);
    
    try {
        const response = await fetch(`${API_BASE}orders.php?action=update-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id, status })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Status updated successfully
        } else {
            alert(data.message);
            // Reload to revert on error
            loadOrders();
        }
    } catch (error) {
        console.error('Error updating status:', error);
        alert('Failed to update status');
        loadOrders();
    }
}

// Close Modal
cancelBtn.addEventListener('click', () => {
    orderModal.classList.remove('show');
});

closeButtons.forEach(btn => {
    btn.addEventListener('click', () => {
        orderModal.classList.remove('show');
        viewModal.classList.remove('show');
        document.getElementById('calendarModal').classList.remove('show');
    });
});

window.addEventListener('click', (e) => {
    if (e.target === orderModal) {
        orderModal.classList.remove('show');
    }
    if (e.target === viewModal) {
        viewModal.classList.remove('show');
    }
    if (e.target === document.getElementById('calendarModal')) {
        document.getElementById('calendarModal').classList.remove('show');
    }
});

// Clear Filters
clearFiltersBtn.addEventListener('click', () => {
    searchInput.value = '';
    dateFilter.value = '';
    statusFilter.value = '';
    clearFiltersBtn.style.display = 'none';
    loadOrders();
});

// Search and Filter
let searchTimeout;
searchInput.addEventListener('input', () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(loadOrders, 300);
});

dateFilter.addEventListener('change', loadOrders);
statusFilter.addEventListener('change', loadOrders);

// Logout
logoutBtn.addEventListener('click', async () => {
    try {
        const response = await fetch(`${API_BASE}auth.php?action=logout`, {
            method: 'POST'
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.location.href = 'login.php';
        }
    } catch (error) {
        console.error('Error logging out:', error);
    }
});

// Show notification toast
function showNotificationToast(message, type = 'info') {
    // Check if toast container exists
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.style.cssText = 'position: fixed; top: 80px; right: 20px; z-index: 10000;';
        document.body.appendChild(toastContainer);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    const colors = {
        success: '#4CAF50',
        error: '#f44336',
        info: '#2196F3',
        warning: '#ff9800'
    };
    
    toast.style.cssText = `
        background: ${colors[type] || colors.info};
        color: white;
        padding: 16px 24px;
        border-radius: 8px;
        margin-bottom: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideIn 0.3s ease;
        max-width: 350px;
        font-size: 14px;
        line-height: 1.4;
    `;
    toast.textContent = message;

    toastContainer.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

// Initialize
loadOrders();
updateSelectedCount();

