const CACHE_VERSION = 'v5';
const HTML_CACHE = 'ajw-pos-html-' + CACHE_VERSION;
const JSON_CACHE = 'ajw-pos-json-' + CACHE_VERSION;
const ASSETS_TO_CACHE = [
    './offline.html',
    './assets/css/style.css',
    './assets/js/app.js',
    'https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap',
    'https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,500,0,0&display=swap'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(HTML_CACHE).then((cache) => cache.addAll(ASSETS_TO_CACHE))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== HTML_CACHE && cacheName !== JSON_CACHE) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;

    // Bypass Service Worker for external CDN requests (like Google Fonts)
    const requestUrl = new URL(event.request.url);
    if (requestUrl.origin !== self.location.origin) {
        return;
    }

    const isAjaxReq = event.request.headers.get('x-requested-with') === 'XMLHttpRequest';
    const acceptsHtml = event.request.headers.get('accept')?.includes('text/html');
    const isNavigate = event.request.mode === 'navigate';

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                const contentType = response.headers.get('content-type') || '';
                const isJson = contentType.includes('application/json');

                if (response && response.status === 200 && response.type === 'basic') {
                    const responseToCache = response.clone();
                    if (isJson || isAjaxReq) {
                        caches.open(JSON_CACHE).then((cache) => cache.put(event.request.url, responseToCache));
                    } else {
                        caches.open(HTML_CACHE).then((cache) => cache.put(event.request.url, responseToCache));
                    }
                }
                return response;
            })
            .catch(() => {
                if (isAjaxReq) {
                    return caches.open(JSON_CACHE).then(cache => cache.match(event.request.url)).then((res) => {
                        if (res) return res;
                        return new Response(JSON.stringify({
                            error: true,
                            flash: { type: 'error', message: "You are offline and no cached data is available for this page." }
                        }), { headers: { 'Content-Type': 'application/json' }});
                    });
                }
                
                if (isNavigate || acceptsHtml) {
                    return caches.open(HTML_CACHE).then(cache => cache.match(event.request.url)).then((cachedResponse) => {
                        return cachedResponse || caches.match('./offline.html');
                    });
                }
                
                return caches.open(HTML_CACHE).then(cache => cache.match(event.request.url));
            })
    );
});
