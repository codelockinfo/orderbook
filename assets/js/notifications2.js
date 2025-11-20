// Push Notification Manager
class NotificationManager {
    constructor() {
        this.isSupported = 'Notification' in window && 'serviceWorker' in navigator && 'PushManager' in window;
        this.publicVapidKey = 'BEl62iUYgUivxIkv69yViEuiBIa-Ib9-SdzE_VO4vWD-rJfKEKqF-pK6Q2c5wEDHEqPkEqQN3rW-qvG9D0Kh2Qc'; // You'll need to generate this
    }

    // Check if notifications are supported
    isNotificationSupported() {
        return this.isSupported;
    }

    // Get current permission status
    getPermissionStatus() {
        if (!this.isSupported) return 'unsupported';
        return Notification.permission;
    }

    // Request notification permission
    async requestPermission() {
        if (!this.isSupported) {
            throw new Error('Push notifications are not supported in this browser');
        }

        const permission = await Notification.requestPermission();
        
        if (permission === 'granted') {
            console.log('Notification permission granted');
            await this.subscribeToPush();
            return true;
        } else if (permission === 'denied') {
            console.warn('Notification permission denied');
            return false;
        } else {
            console.log('Notification permission dismissed');
            return false;
        }
    }

    // Convert VAPID key
    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    // Subscribe to push notifications
    async subscribeToPush() {
        try {
            // Ensure service worker is registered
            if (!('serviceWorker' in navigator)) {
                throw new Error('Service Worker is not supported in this browser');
            }

            // Wait for service worker to be ready
            let registration;
            try {
                registration = await navigator.serviceWorker.ready;
            } catch (error) {
                // If service worker isn't ready, try to register it
                console.log('Service Worker not ready, attempting to register...');
                registration = await navigator.serviceWorker.register('sw.js');
                registration = await navigator.serviceWorker.ready;
            }
            
            if (!registration) {
                throw new Error('Service Worker registration failed');
            }
            
            // Check if already subscribed
            let subscription = await registration.pushManager.getSubscription();
            
            if (!subscription) {
                // Subscribe to push notifications
                const convertedVapidKey = this.urlBase64ToUint8Array(this.publicVapidKey);
                
                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: convertedVapidKey
                });
            }

            // Send subscription to server
            await this.sendSubscriptionToServer(subscription);
            
            console.log('Push subscription successful:', subscription);
            return subscription;
        } catch (error) {
            console.error('Failed to subscribe to push notifications:', error);
            this.showToast(`Failed to enable notifications: ${error.message}`, 'error');
            throw error;
        }
    }

    // Send subscription to server
    async sendSubscriptionToServer(subscription) {
        const subscriptionJson = subscription.toJSON();
        
        try {
            const response = await fetch('api/notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    endpoint: subscription.endpoint,
                    keys: {
                        p256dh: subscriptionJson.keys.p256dh,
                        auth: subscriptionJson.keys.auth
                    }
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Failed to save subscription');
            }
            
            return data;
        } catch (error) {
            console.error('Error sending subscription to server:', error);
            throw error;
        }
    }

    // Unsubscribe from push notifications
    async unsubscribe() {
        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();
            
            if (subscription) {
                // Unsubscribe from push service
                await subscription.unsubscribe();
                
                // Remove from server
                await fetch('api/notifications.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        endpoint: subscription.endpoint
                    })
                });
                
                console.log('Unsubscribed from push notifications');
                return true;
            }
            
            return false;
        } catch (error) {
            console.error('Failed to unsubscribe:', error);
            throw error;
        }
    }

    // Check subscription status
    async checkSubscription() {
        try {
            // First check browser-level subscription
            let browserSubscribed = false;
            if ('serviceWorker' in navigator) {
                try {
                    const registration = await navigator.serviceWorker.ready;
                    const subscription = await registration.pushManager.getSubscription();
                    browserSubscribed = subscription !== null;
                } catch (error) {
                    console.warn('Could not check browser subscription:', error);
                }
            }

            // Then check server-side subscription
            const response = await fetch('api/notifications.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            // Return true only if both browser and server have subscription
            return {
                success: data.success !== false,
                subscribed: data.subscribed && browserSubscribed,
                count: data.count || 0
            };
        } catch (error) {
            console.error('Failed to check subscription:', error);
            return { success: false, subscribed: false, count: 0 };
        }
    }

    // Show notification badge on icon
    updateNotificationBadge(enabled) {
        const badge = document.getElementById('notificationBadge');
        if (badge) {
            badge.style.display = enabled ? 'inline-block' : 'none';
        }
    }

    // Initialize notification UI
    async initializeUI() {
        const notificationBtn = document.getElementById('notificationBtn');
        const notificationToggle = document.getElementById('notificationToggle');
        
        if (!this.isSupported) {
            if (notificationBtn) {
                notificationBtn.disabled = true;
                notificationBtn.title = 'Notifications not supported in this browser';
                notificationBtn.style.opacity = '0.5';
            }
            console.warn('Push notifications are not supported in this browser');
            return;
        }

        console.log('Initializing notification UI...');

        // Check current subscription status
        try {
            const status = await this.checkSubscription();
            console.log('Current subscription status:', status);
            
            if (notificationToggle) {
                notificationToggle.checked = status.subscribed;
            }

            this.updateNotificationBadge(status.subscribed);
        } catch (error) {
            console.error('Error checking subscription status:', error);
        }

        // Add event listeners
        if (notificationBtn) {
            // Remove existing click listeners by cloning (to avoid duplicates)
            const btnClone = notificationBtn.cloneNode(true);
            notificationBtn.parentNode.replaceChild(btnClone, notificationBtn);
            const newNotificationBtn = document.getElementById('notificationBtn');
            
            newNotificationBtn.addEventListener('click', async (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('Notification button clicked');
                try {
                    await this.toggleNotifications();
                } catch (error) {
                    console.error('Error toggling notifications:', error);
                }
            });
            console.log('Notification button event listener added');
        } else {
            console.warn('Notification button not found in DOM');
        }

        if (notificationToggle) {
            notificationToggle.addEventListener('change', async (e) => {
                console.log('Notification toggle changed:', e.target.checked);
                await this.toggleNotifications();
            });
        }
    }

    // Toggle notifications on/off
    async toggleNotifications() {
        const permission = this.getPermissionStatus();
        const notificationToggle = document.getElementById('notificationToggle');
        
        try {
            if (permission === 'granted') {
                // Check if subscribed
                const registration = await navigator.serviceWorker.ready;
                const subscription = await registration.pushManager.getSubscription();
                
                if (subscription) {
                    // Unsubscribe
                    await this.unsubscribe();
                    if (notificationToggle) notificationToggle.checked = false;
                    this.updateNotificationBadge(false);
                    this.showToast('Notifications disabled', 'info');
                    
                    // Dispatch event for auto-trigger
                    window.dispatchEvent(new CustomEvent('notificationStatusChanged', {
                        detail: { enabled: false }
                    }));
                } else {
                    // Subscribe
                    await this.subscribeToPush();
                    if (notificationToggle) notificationToggle.checked = true;
                    this.updateNotificationBadge(true);
                    this.showToast('Notifications enabled! You will receive reminders 1 day before orders.', 'success');
                    
                    // Dispatch event for auto-trigger
                    window.dispatchEvent(new CustomEvent('notificationStatusChanged', {
                        detail: { enabled: true }
                    }));
                }
            } else if (permission === 'denied') {
                this.showToast('Notification permission denied. Please enable in browser settings.', 'error');
                if (notificationToggle) notificationToggle.checked = false;
            } else {
                // Request permission
                const granted = await this.requestPermission();
                if (granted) {
                    if (notificationToggle) notificationToggle.checked = true;
                    this.updateNotificationBadge(true);
                    this.showToast('Notifications enabled! You will receive reminders 1 day before orders.', 'success');
                } else {
                    if (notificationToggle) notificationToggle.checked = false;
                }
            }
        } catch (error) {
            console.error('Error toggling notifications:', error);
            this.showToast('Failed to update notification settings', 'error');
            if (notificationToggle) notificationToggle.checked = false;
        }
    }

    // Show toast notification
    showToast(message, type = 'info') {
        // Check if toast container exists
        let toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toastContainer';
            toastContainer.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000;';
            document.body.appendChild(toastContainer);
        }

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.style.cssText = `
            background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
            color: white;
            padding: 16px 24px;
            border-radius: 8px;
            margin-bottom: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideIn 0.3s ease;
            max-width: 300px;
        `;
        toast.textContent = message;

        toastContainer.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Initialize notification manager
const notificationManager = new NotificationManager();

// Wait for service worker to be ready before initializing UI
function initializeNotifications() {
    // Check if service worker is already ready
    if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
        console.log('Service Worker already active, initializing notifications');
        notificationManager.initializeUI();
    } else {
        // Wait for service worker ready event
        const initOnReady = (event) => {
            console.log('Service Worker ready event received, initializing notifications');
            notificationManager.initializeUI();
            window.removeEventListener('serviceWorkerReady', initOnReady);
        };
        
        window.addEventListener('serviceWorkerReady', initOnReady);
        
        // Fallback: if service worker ready event doesn't fire, wait a bit and initialize anyway
        setTimeout(() => {
            if (document.getElementById('notificationBtn')) {
                console.log('Fallback: Initializing notifications after timeout');
                notificationManager.initializeUI();
                window.removeEventListener('serviceWorkerReady', initOnReady);
            }
        }, 2000);
    }
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeNotifications);
} else {
    initializeNotifications();
}

