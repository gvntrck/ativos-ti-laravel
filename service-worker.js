const CACHE_PREFIX = 'controlepcs';
const CACHE_VERSION = 'v3';
const CACHE_NAME = `${CACHE_PREFIX}-${CACHE_VERSION}`;
const PRECACHE_URLS = [
    './',
    './index.php',
    './manifest.json',
    './assets/icons/icon-192x192.png',
    './assets/icons/icon-512x512.png'
];
const STATIC_ASSET_PATTERN = /\.(?:css|js|png|jpg|jpeg|svg|webp|gif|ico|woff2?)$/i;

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
    const isNavigation = event.request.mode === 'navigate';
    const isStaticAsset = STATIC_ASSET_PATTERN.test(requestUrl.pathname);
    const canCache = isSameOrigin && (isNavigation || isStaticAsset);

    if (!canCache) {
        return;
    }

    event.respondWith((async () => {
        const shouldBypassBrowserCache = isNavigation || /\.(?:css|js)$/i.test(requestUrl.pathname);

        try {
            const networkResponse = shouldBypassBrowserCache
                ? await fetch(event.request, { cache: 'no-store' })
                : await fetch(event.request);

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

            if (isNavigation) {
                const fallback = await caches.match('./index.php');
                if (fallback) {
                    return fallback;
                }
            }

            throw error;
        }
    })());
});
