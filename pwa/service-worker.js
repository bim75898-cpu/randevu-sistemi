const CACHE = 'randevu-system-v1';
const ASSETS = [
  '/randv/pwa/manifest.json',
  '/randv/pwa/icons/icon-192x192.png',
  '/randv/pwa/icons/icon-512x512.png',
  '/randv/pwa/offline.html'
];

self.addEventListener('install', e => {
  e.waitUntil(
    caches.open(CACHE).then(cache => cache.addAll(ASSETS)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys().then(keys => Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', e => {
  const url = new URL(e.request.url);
  if (ASSETS.some(a => url.pathname.endsWith(a))) {
    e.respondWith(
      caches.match(e.request).then(cached => cached || fetch(e.request).catch(() => caches.match('/randv/pwa/offline.html')))
    );
    return;
  }
  if (e.request.mode === 'navigate') {
    e.respondWith(
      fetch(e.request).catch(() => caches.match('/randv/pwa/offline.html'))
    );
  }
});