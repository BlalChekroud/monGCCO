const cacheName = "GCCOM App-v1";

const assets = [
    '/',
    '/manifest.json',
    '/js/mode.js',
    '/js/complement2.js',
    '/offline.html',   // Page de secours
    '/icons/logo_icon_48x48.png',
    '/icons/logo_icon_72x72.png',
    // '/icons/logo_icon_96x96.png',
    // '/icons/logo_icon_128x128.png',
    // '/icons/logo_icon_144x144.png',
    // '/icons/logo_icon_152x152.png',
    // '/icons/logo_icon_192x192.png',
    // '/icons/logo_icon_256x256.png',
    // '/icons/logo_icon_384x384.png',
    '/icons/logo_icon_512x512.png',
    '/asset/styles/app.css',  // Corrigé en /asset au lieu de ../asset
    '/asset/js/main.js',
    '/asset/js/complement.js',
    '/asset/js/sweetalert2.all.js',
    '/js/offline.js',
    '../assets/app.js',
    // '/api/sync-campaign',
    // '/user/counting/campaign/api/sync/', // Endpoint de synchronisation
    // '/user/counting/campaign/',
    // '/user/counting/campaign/17/',
    // '/user/environmental/conditions/',
    // '/user/environmental/conditions/new/',
    // '/user/collected/data/',
    // '/user/collected/data/new/',
    '/user/country/new/',
    '/user/country/',
]


self.addEventListener('install', (installEvent) => {
    installEvent.waitUntil(
        // console.log("Service worker installed", installEvent);
        caches.open(cacheName).then((cache) => {
            return Promise.allSettled(assets.map(asset => cache.add(asset)))
        }).catch((error) => {
            console.error("Erreur lors de la mise en cache", error);
        })
        // .catch((err) => {})
        
        // caches.open(cacheName).then((cache) => {
        //     cache.addAll(assets).then().catch()
        // })
    );
});

self.addEventListener('activate', (activateEvent) => {
    // console,log("SW activated", activateEvent);
    activateEvent.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter((key) => key != cacheName)
                .map((key) => caches.delete(key))
            );
        })
    );
});

self.addEventListener('fetch', (fetchEvent) => {
    // console.log("SW Fetched", fetchEvent);
    // fetchEvent.respondWith(
    //     caches.match(fetchEvent.request).then((res) => {
    //         return res || fetch(fetchEvent.request);
    //     }).catch(() => new Response("Contenu hors ligne non disponible.", {
    //         status: 503,
    //         statusText: "Service Unavailable",
    //     }))
    //     // .catch(() => caches.match("/offline.html")) // Retourne offline.html en cas d'erreur
    // );
    fetchEvent.respondWith(
        caches.match(fetchEvent.request).then((res) => {
            return res || fetch(fetchEvent.request).catch(() => {
                if (fetchEvent.request.url.includes('/api/sync/')) {
                    return new Response(JSON.stringify({ error: "Service hors ligne" }), {
                        headers: { 'Content-Type': 'application/json' },
                        status: 503
                    });
                }
                return caches.match('/offline.html');
            });
        })
    );
    
});


// self.addEventListener('fetch', (fetchEvent) => {
//     const request = fetchEvent.request;
//     const url = new URL(request.url);

//     // Vérifier si c'est une API pour les campagnes, espèces ou données collectées
//     if (url.pathname.startsWith('/api/sync/')) {
//         fetchEvent.respondWith(
//             fetch(request)
//                 .then((response) => {
//                     // Si la réponse est correcte, sauvegarder dans IndexedDB
//                     response.clone().json().then((data) => {
//                         const entityName = url.pathname.split('/').pop(); // ex: "campaign"
//                         saveToIndexedDB(entityName, data); // Fonction IndexedDB
//                     });
//                     return response;
//                 })
//                 .catch(() => {
//                     // Si hors ligne, récupérer depuis IndexedDB
//                     const entityName = url.pathname.split('/').pop(); // ex: "campaign"
//                     return getFromIndexedDB(entityName).then((data) => {
//                         return new Response(JSON.stringify(data), {
//                             headers: { 'Content-Type': 'application/json' },
//                         });
//                     });
//                 })
//         );
//     } else {
//         // Pour les autres requêtes
//         fetchEvent.respondWith(
//             caches.match(request).then((res) => {
//                 return res || fetch(request);
//             }).catch(() => caches.match('/offline.html'))
//         );
//     }
// });