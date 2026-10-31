// DevAltum POS Desktop Edition - Legacy Service Worker Cleanup & Cache Eviction
self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(keys.map((k) => caches.delete(k))))
            .then(() => self.registration.unregister())
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', () => {
    // All requests pass straight through to local PHP server with 0ms latency
    return;
});
