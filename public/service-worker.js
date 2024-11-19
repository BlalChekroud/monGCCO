const cacheName = "GCCOM App-v1";

const assets = [
    '/',
    '/manifest.json',
    '/js/mode.js',
    '/js/complement2.js',
    '/icons/logo_icon_48x48.png',
    '/icons/logo_icon_72x72.png',
    '/icons/logo_icon_96x96.png',
    '/icons/logo_icon_128x128.png',
    '/icons/logo_icon_144x144.png',
    '/icons/logo_icon_152x152.png',
    '/icons/logo_icon_192x192.png',
    '/icons/logo_icon_256x256.png',
    '/icons/logo_icon_384x384.png',
    '/icons/logo_icon_512x512.png',
    '/asset/styles/app.css',  // Corrigé en /asset au lieu de ../asset
    '/asset/js/main.js',
    '/asset/js/complement.js',
    '/asset/js/sweetalert2.all.js',
    '/js/offline.js',
    '../asset/app.js',
    // '/api/sync-campaign',
    // '/user/counting/campaign/api/sync-campaign', // Endpoint de synchronisation
    // '/user/counting/campaign',
    // '/user/counting/campaign/2',
    // '/user/environmental/conditions',
    // '/user/collected/data/',
]


self.addEventListener('install', (installEvent) => {
    installEvent.waitUntil(
        // console.log("Service worker installed", installEvent);
        caches.open(cacheName).then((cache) => {
            return Promise.allSettled(assets.map(asset => cache.add(asset)))
        }).catch((error) => {
            console.error("Error caching assets", error);
        })
        .catch((err) => {})
        
        // caches.open(cacheName).then((cache) => {
        //     cache.addAll(assets).then().catch()
        // })
    )
})

self.addEventListener('activate', (activateEvent) => {
    // console,log("SW activated", activateEvent);
    activateEvent.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter((key) => key != cacheName)
                .map((key) => caches.delete(key))
            )
        })
    )
})

self.addEventListener('fetch', (fetchEvent) => {
    // console.log("SW Fetched", fetchEvent);
    fetchEvent.respondWith(
        caches.match(fetchEvent.request).then((res) => {
            return res || fetch(fetchEvent.request);
        })
    )
})