// Service Worker for PWA

const CACHE_NAME = 'orderbook-v2-notifications';

// Get base path dynamically
const getBasePath = () => {
  // Get the path from the service worker's location
  const swPath = self.location.pathname;
  // Remove 'sw1.js' from the path to get base directory
  return swPath.substring(0, swPath.lastIndexOf('/') + 1);
};

const BASE_PATH = getBasePath();

const urlsToCache = [
  BASE_PATH,
  BASE_PATH + 'index.php',
  BASE_PATH + 'login.php',
  BASE_PATH + 'register.php',
  BASE_PATH + 'assets/css/style2.css',
  BASE_PATH + 'assets/js/app2.js',
  BASE_PATH + 'assets/js/auth1.js',
  BASE_PATH + 'assets/js/calendar.js',
  BASE_PATH + 'manifest.json'
];

// Install Service Worker
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('Opened cache');
        return cache.addAll(urlsToCache);
      })
  );
});

// Activate Service Worker
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            console.log('Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Fetch Event - Network First, then Cache
self.addEventListener('fetch', (event) => {
  event.respondWith(
    fetch(event.request)
      .then((response) => {
        // Clone the response
        const responseToCache = response.clone();
        
        caches.open(CACHE_NAME)
          .then((cache) => {
            cache.put(event.request, responseToCache);
          });
        
        return response;
      })
      .catch(() => {
        return caches.match(event.request);
      })
  );
});

// Background Sync
self.addEventListener('sync', (event) => {
  if (event.tag === 'sync-orders') {
    event.waitUntil(syncOrders());
  }
});

async function syncOrders() {
  // Implement sync logic here
  console.log('Syncing orders...');
}

// Push Notifications
self.addEventListener('push', (event) => {
  let notificationData = {
    title: 'Order Reminder',
    body: 'You have an upcoming order tomorrow!',
    tag: 'order-reminder',
    data: {},
    vibrate: [200, 100, 200],
    requireInteraction: false
  };

  // Parse notification data if available
  if (event.data) {
    try {
      const payload = event.data.json();
      notificationData = {
        title: payload.title || notificationData.title,
        body: payload.body || notificationData.body,
        tag: payload.tag || notificationData.tag,
        data: payload.data || {},
        vibrate: payload.vibrate || [200, 100, 200],
        requireInteraction: payload.requireInteraction || false
      };
      
      // Only include icon if provided in payload (icons must exist)
      if (payload.icon) notificationData.icon = payload.icon;
      if (payload.badge) notificationData.badge = payload.badge;
      
      // Only include actions if icon paths are provided
      if (payload.actions) {
        notificationData.actions = payload.actions;
      }
    } catch (e) {
      console.error('Error parsing notification data:', e);
      try {
        notificationData.body = event.data.text();
      } catch (textError) {
        notificationData.body = 'You have a notification';
      }
    }
  }
  
  event.waitUntil(
    self.registration.showNotification(notificationData.title, notificationData)
  );
});

// Notification Click
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  
  const action = event.action;
  const notificationData = event.notification.data || {};
  
  if (action === 'close') {
    // Just close the notification
    return;
  }
  
  // Default action or 'view' action
  const basePath = getBasePath();
  const urlToOpen = notificationData.url || basePath + 'index.php';
  
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then((clientList) => {
        // Check if app is already open (check for base path in URL)
        for (let client of clientList) {
          if (client.url.includes(basePath) && 'focus' in client) {
            return client.focus();
          }
        }
        // Open new window if not already open
        if (clients.openWindow) {
          return clients.openWindow(urlToOpen);
        }
      })
  );
});

