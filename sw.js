// Auto-updating service worker with version management
const CACHE_VERSION = '1.0.6'; // Increment this when you want to force updates
const CACHE_NAME = `bm-scapis-v${CACHE_VERSION}`;

// Core URLs to cache - only essential resources
const urlsToCache = [
  '/muhai_malangit/',
  '/muhai_malangit/index.php',
  '/muhai_malangit/login.php',
  '/muhai_malangit/register.php',
  '/muhai_malangit/dashboard.php',
  '/muhai_malangit/offline.html',
  '/muhai_malangit/manifest.json?v=1.0.6',
  '/muhai_malangit/assets/images/logo-192.png?v=1.0.6',
  '/muhai_malangit/assets/images/logo-512.png?v=1.0.6',
  '/muhai_malangit/assets/images/logo-96.png?v=1.0.6'
];

// External resources to cache
const externalUrlsToCache = [
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js'
];

// Install event - cache resources
self.addEventListener('install', event => {
  console.log(`Service Worker installing version ${CACHE_VERSION}`);
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Opened cache:', CACHE_NAME);
        
        // Cache core URLs first
        const coreCachePromises = urlsToCache.map(url => {
          return cache.add(url).catch(error => {
            console.warn(`Failed to cache ${url}:`, error);
            return null; // Don't fail the entire cache operation
          });
        });
        
        // Cache external resources
        const externalCachePromises = externalUrlsToCache.map(url => {
          return cache.add(url).catch(error => {
            console.warn(`Failed to cache external resource ${url}:`, error);
            return null;
          });
        });
        
        return Promise.all([...coreCachePromises, ...externalCachePromises]);
      })
      .then(results => {
        const successfulCaches = results.filter(result => result !== null).length;
        console.log(`Successfully cached ${successfulCaches} resources`);
      })
      .catch(error => {
        console.error('Cache install failed:', error);
      })
  );
  // Force the waiting service worker to become the active service worker
  self.skipWaiting();
});

// Activate event - clean up old caches and take control
self.addEventListener('activate', event => {
  console.log(`Service Worker activating version ${CACHE_VERSION}`);
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            console.log('Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => {
      // Take control of all clients immediately
      return self.clients.claim();
    })
  );
});

// Fetch event - network first with cache fallback
self.addEventListener('fetch', event => {
  // Only cache GET requests
  if (event.request.method !== 'GET') {
    return event.respondWith(fetch(event.request));
  }

  // Skip caching for various types of requests
  const skipCache = 
    event.request.url.includes('/ajax/') || 
    event.request.url.includes('?ajax=1') ||
    event.request.url.includes('process-') ||
    event.request.url.includes('update-') ||
    event.request.url.includes('delete-') ||
    event.request.url.includes('chrome-extension://') ||
    event.request.url.includes('moz-extension://') ||
    event.request.url.includes('safari-extension://') ||
    event.request.url.includes('ms-browser-extension://') ||
    event.request.url.includes('data:') ||
    event.request.url.includes('blob:') ||
    event.request.url.includes('file:');

  if (skipCache) {
    return event.respondWith(fetch(event.request));
  }

  event.respondWith(
    fetch(event.request)
      .then(response => {
        // Check if we received a valid response
        if (!response || response.status !== 200 || response.type !== 'basic') {
          return response;
        }
        
        // Clone the response
        const responseToCache = response.clone();
        
        // Only cache successful responses
        caches.open(CACHE_NAME)
          .then(cache => {
            cache.put(event.request, responseToCache).catch(error => {
              console.warn('Failed to cache response:', error);
            });
          })
          .catch(error => {
            console.warn('Failed to open cache for storing:', error);
          });
        
        return response;
      })
      .catch(() => {
        // Return cached version if network fails
        return caches.match(event.request)
          .then(cachedResponse => {
            if (cachedResponse) {
              return cachedResponse;
            }
            
            // Return offline page for navigation requests
            if (event.request.mode === 'navigate') {
              return caches.match('/muhai_malangit/offline.html');
            }
          });
      })
  );
});

// Background sync
self.addEventListener('sync', event => {
  if (event.tag === 'background-sync') {
    event.waitUntil(doBackgroundSync());
  }
});

function doBackgroundSync() {
  // Handle background sync operations
  console.log('Background sync triggered');
}

// Push notifications
self.addEventListener('push', event => {
  const options = {
    body: event.data ? event.data.text() : 'New notification from BM-SCaPIS',
    icon: '/muhai_malangit/assets/images/logo-192.png?v=1.0.6',
    badge: '/muhai_malangit/assets/images/logo-96.png?v=1.0.6',
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: '1'
    },
    actions: [
      {
        action: 'explore',
        title: 'View Details',
        icon: '/muhai_malangit/assets/images/logo-96.png?v=1.0.6'
      },
      {
        action: 'close',
        title: 'Close',
        icon: '/muhai_malangit/assets/images/logo-96.png?v=1.0.6'
      }
    ]
  };
  
  event.waitUntil(
    self.registration.showNotification('BM-SCaPIS', options)
  );
});

// Notification click
self.addEventListener('notificationclick', event => {
  console.log('Notification click received.');
  
  event.notification.close();
  
  if (event.action === 'explore') {
    event.waitUntil(clients.openWindow('/muhai_malangit/dashboard.php'));
  } else if (event.action === 'close') {
    // Just close the notification
  } else {
    event.waitUntil(clients.openWindow('/muhai_malangit/'));
  }
});

// Message event for communication with main thread
self.addEventListener('message', event => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
  
  if (event.data && event.data.type === 'GET_VERSION') {
    event.ports[0].postMessage({ version: CACHE_VERSION });
  }
  
  if (event.data && event.data.type === 'CLEAR_ALL_CACHES') {
    event.waitUntil(
      caches.keys().then(cacheNames => {
        return Promise.all(
          cacheNames.map(cacheName => caches.delete(cacheName))
        );
      })
    );
  }
});
