/* File: sheener/service-worker.js */
// Service Worker for PWA functionality
const CACHE_NAME = 'sheener-reporter-v7';
const urlsToCache = [
  '/sheener/index.php',
  '/sheener/mobile_report.php',
  '/sheener/mobile_report.php?source=pwa',
  '/sheener/css/styles.css',
  '/sheener/css/buttons.css',
  '/sheener/js/script.js',
  '/sheener/js/offline-storage.js',
  '/sheener/js/sync-manager.js',
  '/sheener/img/Amneal_Logo_new.svg',
  '/sheener/img/Amneal_A_Logo_new.svg',
  '/sheener/img/favicon/faviconAY.ico',
  '/sheener/manifest.json'
];

// Install event - cache resources
self.addEventListener('install', function (event) {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(function (cache) {
        console.log('Opened cache');
        // Filter out any invalid URLs before caching
        const validUrls = urlsToCache.filter(function (url) {
          try {
            const urlObj = new URL(url, self.location.origin);
            return urlObj.protocol.startsWith('http');
          } catch (e) {
            return false;
          }
        });
        // Cache valid URLs one by one to handle individual failures
        return Promise.allSettled(
          validUrls.map(function (url) {
            return cache.add(url).catch(function (error) {
              console.log('Failed to cache:', url, error.message);
              return null; // Continue even if one fails
            });
          })
        );
      })
      .catch(function (error) {
        console.log('Cache installation failed:', error);
      })
  );
});

// Helper function to check if URL is cacheable
function isCacheableRequest(request) {
  try {
    const urlString = request.url || '';

    // Quick string check first - most efficient
    if (urlString.startsWith('chrome-extension://') ||
      urlString.startsWith('moz-extension://') ||
      urlString.startsWith('safari-extension://') ||
      urlString.startsWith('edge-extension://') ||
      urlString.startsWith('chrome://') ||
      urlString.startsWith('about:') ||
      urlString.startsWith('data:') ||
      urlString.startsWith('blob:')) {
      return false;
    }

    // Only cache HTTP/HTTPS URLs
    if (!urlString.startsWith('http://') && !urlString.startsWith('https://')) {
      return false;
    }

    return true;
  } catch (error) {
    // If anything fails, don't cache
    return false;
  }
}

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', function (event) {
  // Skip caching for non-HTTP requests (extensions, etc.)
  if (!isCacheableRequest(event.request)) {
    event.respondWith(fetch(event.request));
    return;
  }

  // Always fetch API calls from network
  if (event.request.url.includes('submit_anonymous_event.php')) {
    event.respondWith(
      fetch(event.request).catch(() => {
        // Return offline response if fetch fails
        return new Response(JSON.stringify({
          success: false,
          error: 'Network unavailable. Event saved offline.'
        }), {
          headers: { 'Content-Type': 'application/json' }
        });
      })
    );
    return;
  }

  // For other resources, use cache-first strategy
  // First check if request is cacheable
  if (!isCacheableRequest(event.request)) {
    // For non-cacheable requests (like chrome-extension), just fetch directly
    event.respondWith(fetch(event.request));
    return;
  }

  event.respondWith(
    caches.match(event.request)
      .then(function (response) {
        // Cache hit - return response
        if (response) {
          return response;
        }
        return fetch(event.request).then(function (fetchResponse) {
          // Cache successful responses (only if cacheable and valid)
          // Only attempt to cache if it's a cacheable request
          if (fetchResponse && fetchResponse.status === 200) {
            // Final check before attempting cache operation
            if (isCacheableRequest(event.request)) {
              try {
                const responseToCache = fetchResponse.clone();
                caches.open(CACHE_NAME).then(function (cache) {
                  // One more check right before put
                  if (isCacheableRequest(event.request)) {
                    cache.put(event.request, responseToCache).catch(function (error) {
                      // Silently ignore chrome-extension errors
                      if (!error.message || !error.message.includes('chrome-extension')) {
                        console.log('Cache put failed:', error.message);
                      }
                    });
                  }
                }).catch(function (error) {
                  // Ignore cache open errors silently
                });
              } catch (error) {
                // Ignore clone/operation errors silently
              }
            }
          }
          return fetchResponse;
        }).catch(function (error) {
          // Network error - return cached version if available
          return caches.match(event.request).then(function (cachedResponse) {
            return cachedResponse || new Response('Network error', { status: 408 });
          });
        });
      })
  );
});

// Background Sync for offline event submission
self.addEventListener('sync', function (event) {
  if (event.tag === 'sync-events') {
    event.waitUntil(
      // Trigger sync when service worker comes online
      self.clients.matchAll().then(function (clients) {
        clients.forEach(function (client) {
          client.postMessage({
            type: 'SYNC_EVENTS'
          });
        });
      })
    );
  }
});

// Message handler for sync requests
self.addEventListener('message', function (event) {
  if (event.data && event.data.type === 'SYNC_EVENTS') {
    event.waitUntil(
      self.clients.matchAll().then(function (clients) {
        clients.forEach(function (client) {
          client.postMessage({
            type: 'SYNC_EVENTS'
          });
        });
      })
    );
  }
});

// Activate event - clean up old caches
self.addEventListener('activate', function (event) {
  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then(function (cacheNames) {
      return Promise.all(
        cacheNames.map(function (cacheName) {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});
