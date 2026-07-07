/**
 * ARCH-006: Service Worker – App-Shell-Cache für Heldenregister
 *
 * Strategie:
 *   HTML-Navigations-Requests → Network-First (immer frische Seite, kein Token-Problem)
 *   Statische Assets (CSS/JS/Bilder/Fonts/Icons) → Cache-First (Vite-Bundle mit Hash-Namen)
 *
 * UI-45: Offline-Fallback (/offline.html) für Navigations-Requests bei fehlendem Netz.
 */

const CACHE_VERSION = 'v2';
const SHELL_CACHE   = 'heldenregister-shell-' + CACHE_VERSION;

// Assets die beim SW-Install sofort gecacht werden (App-Shell).
const PRECACHE_URLS = [
    '/manifest.webmanifest',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/favicon.ico',
    '/offline.html',
];

// ----------------------------------------------------------------
// Install: App-Shell precachen
// ----------------------------------------------------------------
self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(SHELL_CACHE).then(function (cache) {
            return cache.addAll(PRECACHE_URLS);
        }).then(function () {
            return self.skipWaiting();
        })
    );
});

// ----------------------------------------------------------------
// Activate: alte Cache-Versionen entfernen
// ----------------------------------------------------------------
self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(
                keys.filter(function (key) {
                    return key.startsWith('heldenregister-') && key !== SHELL_CACHE;
                }).map(function (key) {
                    return caches.delete(key);
                })
            );
        }).then(function () {
            return self.clients.claim();
        })
    );
});

// ----------------------------------------------------------------
// Fetch: Routing-Strategie je Request-Typ
// ----------------------------------------------------------------
self.addEventListener('fetch', function (event) {
    const req = event.request;

    // Nur GET-Anfragen behandeln; POST/PUT/DELETE immer nativ.
    if (req.method !== 'GET') return;

    // Nur same-origin; externe Requests (Google Fonts, CDN) überspringen.
    const url = new URL(req.url);
    if (url.origin !== self.location.origin) return;

    // HTML-Navigation → Network-First: immer frische Serverantwort, kein Cache.
    // UI-45: Bei Offline-Fehler die gecachte /offline.html ausliefern.
    if (req.mode === 'navigate' || req.headers.get('Accept').includes('text/html')) {
        event.respondWith(
            fetch(req).catch(function () {
                return caches.match('/offline.html').then(function (cached) {
                    return cached || new Response(
                        '<h1>Offline</h1><p>Bitte Verbindung prüfen.</p>',
                        { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
                    );
                });
            })
        );
        return;
    }

    // Statische Assets (Vite-Bundle, Icons, Bilder, Fonts) → Cache-First.
    // Vite erzeugt Content-Hash-Namen → gecachte Version ist immer korrekt.
    if (
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/icons/') ||
        url.pathname.startsWith('/css/') ||
        url.pathname.startsWith('/storage/') ||
        url.pathname === '/favicon.ico' ||
        url.pathname === '/manifest.webmanifest'
    ) {
        event.respondWith(
            caches.match(req).then(function (cached) {
                if (cached) return cached;
                return fetch(req).then(function (response) {
                    if (!response || response.status !== 200 || response.type !== 'basic') {
                        return response;
                    }
                    const toCache = response.clone();
                    caches.open(SHELL_CACHE).then(function (cache) {
                        cache.put(req, toCache);
                    });
                    return response;
                });
            })
        );
        return;
    }

    // Alle anderen GET-Anfragen (AJAX, API) → immer Network.
});
