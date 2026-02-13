const CACHE_PREFIX = 'controlepcs';
const CACHE_VERSION = 'v5';
const CACHE_NAME = `${CACHE_PREFIX}-${CACHE_VERSION}`;
const PRECACHE_URLS = [
    './manifest.json',
    './assets/icons/icon-192x192.png',
    './assets/icons/icon-512x512.png'
];
const PWA_ASSET_PATTERN = /(?:\/manifest\.json$|\/assets\/icons\/.+\.(?:png|svg|ico)$)/i;

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(cacheNames => Promise.all(
                cacheNames
                    .filter(cacheName => cacheName.startsWith(`${CACHE_PREFIX}-`) && cacheName !== CACHE_NAME)
                    .map(cacheName => caches.delete(cacheName))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('message', event => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

self.addEventListener('fetch', event => {
    if (event.request.method !== 'GET') {
        return;
    }

    const requestUrl = new URL(event.request.url);
    const isSameOrigin = requestUrl.origin === self.location.origin;
    if (!isSameOrigin) {
        return;
    }

    // Dynamic application pages must always come from network to avoid stale UI.
    if (event.request.mode === 'navigate') {
        return;
    }

    const isPwaAsset = PWA_ASSET_PATTERN.test(requestUrl.pathname);
    if (!isPwaAsset) {
        return;
    }

    event.respondWith((async () => {
        try {
            const networkResponse = await fetch(event.request, { cache: 'no-store' });

            if (networkResponse && networkResponse.ok && networkResponse.type === 'basic') {
                const cache = await caches.open(CACHE_NAME);
                await cache.put(event.request, networkResponse.clone());
            }

            return networkResponse;
        } catch (error) {
            const cachedResponse = await caches.match(event.request);
            if (cachedResponse) {
                return cachedResponse;
            }

            throw error;
        }
    })());
});
