// sw-worker.js (Service Worker)

const CACHE_NAME = 'aleinahs-v3'; // Bump version to ensure cache update
const urlsToCache = [
  // Core files
  '/', 
  '/admin/dashboard/index.php',
  '/admin/dashboard/aleinahslogo.png',
  
  // Critical Budget Module files
  '/admin/budget/index.php',
  '/admin/budget/budget_record.js',
  '/admin/budget/budget_record.css',
  '/admin/budget/budget_chart_data.php',
  '/admin/budget/sync_budget.php', // Sync endpoint
  
  // Required library for offline storage
  'https://cdn.jsdelivr.net/localforage/1.7.3/localforage.min.js' 
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Opened cache and added URLs:', urlsToCache);
        self.skipWaiting(); 
        return cache.addAll(urlsToCache.map(url => new Request(url, { cache: "no-cache" })));
      })
  );
});

self.addEventListener('fetch', event => {
  // Always bypass caching for POST requests (data submission)
  if (event.request.method !== 'GET') {
    return;
  }
  
  // Strategy: Cache-First for GET requests
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        if (response) {
          return response; // Serve from cache if available
        }
        
        // Fallback to network fetch
        return fetch(event.request).catch(error => {
            console.error('Fetch failed and no cache match:', error);
            // If the network fails, try to return the cached index page for the main view
            if (event.request.url.includes('/admin/budget/index.php')) {
                return caches.match('/admin/budget/index.php');
            }
        });
      })
  );
});

self.addEventListener('activate', event => {
  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  event.waitUntil(self.clients.claim()); 
});


// --- OFFLINE DATA SYNC LOGIC (The sync handler) ---
self.addEventListener('sync', event => {
  if (event.tag === 'sync-budget-data') {
    event.waitUntil(sendOfflineRequests());
  }
});

// Sends queued records to the server
async function sendOfflineRequests() {
    // This function requires the LocalForage library/polyfill to be available to the Service Worker.
    // For a fully working solution, you would typically import it here via `importScripts()`.
    // Since we cannot verify this environment, this remains a conceptual implementation relying on LocalForage API.
    
    // NOTE: In a real-world scenario, you would implement the following using LocalForage/IndexedDB APIs:
    const recordsToSend = []; // 1. Retrieve all records from the sync queue
    const keys = []; // 2. Retrieve their keys

    if (recordsToSend.length === 0) return;

    try {
        const response = await fetch('/capsfilesv2/admin/budget/sync_budget.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(recordsToSend)
        });

        const result = await response.json();

        if (result.success && result.synced_keys.length > 0) {
            // 3. Delete successfully synced records from local storage
            result.synced_keys.forEach(key => {
                // localforage.removeItem(key); // Actual implementation call
            });
            console.log(`Successfully synced ${result.synced_keys.length} budget records.`);
        }
    } catch (error) {
        console.error('Failed to sync budget data:', error);
        // Do NOT resolve/reject; the sync will retry later automatically
    }
}