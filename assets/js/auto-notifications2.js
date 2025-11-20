/**
 * Automatic Notification Trigger
 * 
 * This script automatically checks and sends notifications when user has notifications enabled
 * Runs every hour in the background
 */

class AutoNotificationTrigger {
    constructor() {
        this.checkInterval = 60 * 60 * 1000; // 1 hour in milliseconds
        this.intervalId = null;
        this.lastTriggerTime = null;
        this.isRunning = false;
        
        // Load last trigger time from localStorage
        const saved = localStorage.getItem('lastNotificationTrigger');
        if (saved) {
            this.lastTriggerTime = new Date(saved);
        }
    }
    
    /**
     * Start auto-triggering
     */
    start() {
        if (this.isRunning) {
            console.log('Auto-trigger already running');
            return;
        }
        
        console.log('🔔 Auto-notification trigger started');
        this.isRunning = true;
        
        // Check if we should trigger immediately
        this.checkAndTrigger();
        
        // Set up interval to check every hour
        this.intervalId = setInterval(() => {
            this.checkAndTrigger();
        }, this.checkInterval);
        
        // Also check when page becomes visible again
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.checkAndTrigger();
            }
        });
    }
    
    /**
     * Stop auto-triggering
     */
    stop() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
        this.isRunning = false;
        console.log('🔕 Auto-notification trigger stopped');
    }
    
    /**
     * Check if we should trigger and do it
     */
    async checkAndTrigger() {
        // Check if enough time has passed since last trigger (55 minutes minimum)
        const now = new Date();
        if (this.lastTriggerTime) {
            const timeSince = now - this.lastTriggerTime;
            const minInterval = 55 * 60 * 1000; // 55 minutes
            
            if (timeSince < minInterval) {
                console.log(`⏳ Waiting... Last trigger was ${Math.round(timeSince / 60000)} minutes ago`);
                return;
            }
        }
        
        // Check if we're in a notification time window
        const hour = now.getHours();
        const isInWindow = (hour >= 8 && hour < 23); // 8 AM to 11 PM
        
        if (!isInWindow) {
            console.log(`🌙 Outside notification windows (current hour: ${hour})`);
            return;
        }
        
        // Trigger notifications
        await this.trigger();
    }
    
    /**
     * Trigger the notification check
     */
    async trigger() {
        try {
            console.log('🔔 Triggering automatic notification check...');
            
            const response = await fetch('cron/auto-trigger.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                console.log('✅ Auto-trigger successful:', data);
                
                // Update last trigger time
                this.lastTriggerTime = new Date();
                localStorage.setItem('lastNotificationTrigger', this.lastTriggerTime.toISOString());
                
                // Show notification count if any were sent
                if (data.result && data.result.sent > 0) {
                    this.showNotification(`📬 ${data.result.sent} notification(s) sent automatically`);
                }
            } else {
                console.error('❌ Auto-trigger failed:', data.error);
            }
            
        } catch (error) {
            console.error('❌ Auto-trigger error:', error);
        }
    }
    
    /**
     * Show a small toast notification
     */
    showNotification(message) {
        // Check if toast container exists
        let toastContainer = document.getElementById('autoTriggerToast');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'autoTriggerToast';
            toastContainer.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 9999;
                background: #4CAF50;
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                font-size: 14px;
                opacity: 0;
                transition: opacity 0.3s ease;
            `;
            document.body.appendChild(toastContainer);
        }
        
        toastContainer.textContent = message;
        toastContainer.style.opacity = '1';
        
        setTimeout(() => {
            toastContainer.style.opacity = '0';
        }, 3000);
    }
    
    /**
     * Get status info
     */
    getStatus() {
        return {
            isRunning: this.isRunning,
            lastTrigger: this.lastTriggerTime,
            nextCheck: this.lastTriggerTime ? new Date(this.lastTriggerTime.getTime() + this.checkInterval) : 'Soon'
        };
    }
}

// Create global instance
window.autoNotificationTrigger = new AutoNotificationTrigger();

// Auto-start if notifications are enabled
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAutoTrigger);
} else {
    initAutoTrigger();
}

async function initAutoTrigger() {
    // Wait a bit for notification manager to initialize
    setTimeout(async () => {
        if (typeof notificationManager !== 'undefined') {
            // Check if user has notifications enabled
            const status = await notificationManager.checkSubscription();
            
            if (status.subscribed && Notification.permission === 'granted') {
                console.log('✅ Notifications enabled - Starting auto-trigger');
                window.autoNotificationTrigger.start();
            } else {
                console.log('ℹ️ Notifications not enabled - Auto-trigger disabled');
            }
        }
    }, 2000);
}

// Listen for notification toggle changes
window.addEventListener('notificationStatusChanged', (event) => {
    if (event.detail.enabled) {
        console.log('🔔 Notifications enabled - Starting auto-trigger');
        window.autoNotificationTrigger.start();
    } else {
        console.log('🔕 Notifications disabled - Stopping auto-trigger');
        window.autoNotificationTrigger.stop();
    }
});

