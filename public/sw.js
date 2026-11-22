/* Hisab service worker — app shell caching + offline fallback.
 * Bump CACHE when the shell changes. Livewire requests are never cached.
 * CSS/JS use stale-while-revalidate: serve the cached copy instantly and
 * fetch a fresh copy in the background, so a deployed stylesheet can never
 * stay stale on a phone that visited before. */
const CACHE = 'hisab-v3';
const SHELL = [
  '/',
 '/css/app.css',
 '/manifest.json',
 '/icons/icon-192.png',
 '/icons/icon-512.png',
 '/icons/apple-touch-icon.png',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE).then((cache) => cache.addAll(SHELL)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

/* Stale-while-revalidate: answer instantly from cache when possible,
 * and fetch a fresh copy in the background to update the cache. */
function staleWhileRevalidate(req) {
  return caches.open(CACHE).then((cache) =>
    cache.match(req).then((hit) => {
      const network = fetch(req)
        .then((res) => {
          if (res.ok) cache.put(req, res.clone());
          return res;
        })
        .catch(() => undefined);
      return hit || network;
    })
  );
}

function offlineResponse() {
  return new Response(
    '<!DOCTYPE html><html lang="bn"><head><meta charset="utf-8">' +
    '<meta name="viewport" content="width=device-width,initial-scale=1">' +
    '<title>হিসাব</title></head>' +
    '<body style="font-family:sans-serif;text-align:center;padding:48px 20px;color:#0B1F17">' +
    '<h1>হিসাব</h1><p>ইন্টারনেট সংযোগ নেই — সংযোগ ফিরলে আবার চেষ্টা করুন।</p>' +
    '</body></html>',
    { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
  );
}

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.origin !== self.location.origin) return;
  // Livewire updates are live data — never serve or store them from cache.
  if (url.pathname.startsWith('/livewire/')) return;

  // Navigations: network first, fall back to cache, then the offline page.
  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req)
        .then((res) => {
          if (res.ok) {
            const copy = res.clone();
            caches.open(CACHE).then((cache) => cache.put(req, copy));
          }
          return res;
        })
        .catch(() => caches.match(req).then((hit) => hit || caches.match('/')).then((hit) => hit || offlineResponse()))
    );
    return;
  }

  // CSS/JS: stale-while-revalidate — instant from cache, fresh in background.
  if (/\.(css|js)$/i.test(url.pathname)) {
    event.respondWith(staleWhileRevalidate(req));
    return;
  }

  // Images, fonts, icons, manifest: cache first.
  if (/\.(png|jpg|jpeg|svg|webp|ico|woff2?|ttf)$/i.test(url.pathname) || url.pathname === '/manifest.json') {
    event.respondWith(
      caches.match(req).then(
        (hit) =>
          hit ||
          fetch(req).then((res) => {
            if (res.ok) {
              const copy = res.clone();
              caches.open(CACHE).then((cache) => cache.put(req, copy));
            }
            return res;
          })
      )
    );
  }
  // Every other same-origin GET (dynamic pages): network only.
});
