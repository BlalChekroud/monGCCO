const PREFIX = "V1";
const BASE = location.protocol + "//" + location.host;
const CACHED_FILES = [
    // `${BASE}/user/country/new/`,
    `${BASE}/user/collected/data/`,
    "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css",
    // `${BASE}/user/counting/campaign/`,
];

self.addEventListener("install", (event) => {
    self.skipWaiting();
    event.waitUntil(
        (async () => {
            const cache = await caches.open(PREFIX);
            await cache.addAll([...CACHED_FILES, "/offline.html"]);
            // await Promise.all(
            //     [...CACHED_FILES, "/offline.html"].map((path) => {
            //         return cache.add(new Request(path));
            //     })
            // );
        })()
    );
    console.log(`${PREFIX} Install`);
});

self.addEventListener("activate", (event) => {
    clients.claim();
    event.waitUntil(
        (async () => {
            const keys = await caches.keys();
            await Promise.all(
                keys.map((key) => {
                    if (!key.includes(PREFIX)) { // if (key !== PREFIX) {
                        return caches.delete(key);
                    }
                })
            );
        })()
    );
    console.log(`${PREFIX} Activate`);
});

self.addEventListener("fetch", (event) => {
    console.log(`${PREFIX} Fetching : ${event.request.url}, Mode : ${event.request.mode}`);


    if (!navigator.onLine) {
        // Vous pouvez ici implémenter une réponse hors ligne
        console.log("Requête interceptée hors ligne :", event.request.url);
    }


    if(event.request.mode === 'navigate') {
        event.respondWith(
            (async () => {
                try {
                    const preloadResponse = await event.preloadResponse;
                    if (preloadResponse) {
                        return preloadResponse;
                    }

                    return await fetch(event.request);
                } catch (error) {
                    const cache = await caches.open(PREFIX);
                    return await cache.match(`${BASE}/offline.html`);
                }
            })()
            );
    }
});