const CACHE_NAME = "wingwatch-cache-v1";
const METADATA_URLS = [
  "/api/metadata/campaigns",
  "/api/metadata/species",
  "/api/metadata/countType",
  "/api/metadata/quality",
  "/api/metadata/method",
];
const SYNC_TAG = "sync-collects";
const DB_NAME = "WingWatchDB";
const STORE_NAME = "collects";

// Fonction utilitaire pour ouvrir IndexedDB
function openDb() {
  return new Promise((resolve, reject) => {
    const req = indexedDB.open(DB_NAME, 1);
    
    req.onupgradeneeded = () => {
      const db = req.result;
      if (!db.objectStoreNames.contains(STORE_NAME)) {
        db.createObjectStore(STORE_NAME, { keyPath: "id", autoIncrement: true });
      }
    };
    
    req.onsuccess = () => resolve(req.result);
    req.onerror = () => reject(req.error);
  });
}

// Gestion des collectes en attente
async function getPendingCollects() {
  const db = await openDb();
  return new Promise((resolve) => {
    const tx = db.transaction(STORE_NAME, "readonly");
    const store = tx.objectStore(STORE_NAME);
    const req = store.getAll();
    req.onsuccess = () => resolve(req.result);
    req.onerror = () => resolve([]);
  });
}

async function removeCollect(id) {
  const db = await openDb();
  return new Promise((resolve) => {
    const tx = db.transaction(STORE_NAME, "readwrite");
    tx.objectStore(STORE_NAME).delete(id);
    tx.oncomplete = () => resolve();
  });
}

// Installation
self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) =>
      cache.addAll([
        "/",
        "/offline.html",
        // "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css",
        ...METADATA_URLS
      ])
    )
  );
  self.skipWaiting();
});

// Activation
self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.map((key) => key !== CACHE_NAME && caches.delete(key)))
    )
  );
  self.clients.claim();
});

// Fetch handler
self.addEventListener("fetch", (event) => {
  const { request } = event;

  // Vérifier si l'URL est supportée pour la mise en cache
  const url = new URL(request.url);
  if (!['http:', 'https:'].includes(url.protocol)) {
    return;
  }

  // Intercepter les requêtes POST de collecte
  if (request.method === 'POST' && url.pathname === '/user/collected/data/api/collects') {
    event.respondWith(
      fetch(request.clone())
        .catch(async (err) => {
          // Si hors ligne, sauvegarder dans IndexedDB
          const payload = await request.clone().json();
          const headers = {};
          request.headers.forEach((value, key) => {
            headers[key] = value;
          });
          
          const db = await openDb();
          const tx = db.transaction(STORE_NAME, "readwrite");
          const store = tx.objectStore(STORE_NAME);
          
          const collectData = {
            payload: payload,
            csrfToken: headers['x-csrf-token'] || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
          };
          
          await store.add(collectData);
          console.log("💾 Collecte sauvegardée hors ligne:", collectData);
          
          // Enregistrer une demande de synchronisation
          await self.registration.sync.register(SYNC_TAG);
          
          // Retourner une réponse de succès
          return new Response(JSON.stringify({
            success: true,
            message: "Collecte sauvegardée hors ligne"
          }), {
            headers: { 'Content-Type': 'application/json' }
          });
        })
    );
    return;
  }

  // Ne pas intercepter les autres requêtes POST
  if (request.method === 'POST') {
    return;
  }

  // Stratégie pour les endpoints metadata
  if (METADATA_URLS.some((metadataUrl) => url.pathname.includes(metadataUrl))) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          if (!response.ok) {
            throw new Error('Network response was not ok');
          }
          const clone = response.clone();
          caches.open(CACHE_NAME).then((cache) => {
            // Vérifier à nouveau le protocole avant la mise en cache
            if (['http:', 'https:'].includes(url.protocol)) {
              cache.put(request, clone);
            }
          });
          return response;
        })
        .catch(() => {
          return caches.match(request)
            .then(cached => {
              if (cached) return cached;
              return new Response(JSON.stringify({ error: 'Network error' }), {
                headers: { 'Content-Type': 'application/json' }
              });
            });
        })
    );
    return;
  }

  // Stratégie pour les autres requêtes
  event.respondWith(
    caches.match(request)
      .then((cached) => {
        if (cached) return cached;
        
        return fetch(request)
          .then(response => {
            // Mettre en cache les réponses réussies
            if (response.ok && request.method === 'GET') {
              const clone = response.clone();
              caches.open(CACHE_NAME)
                .then((cache) => {
                  const requestUrl = new URL(request.url);
                  if (['http:', 'https:'].includes(requestUrl.protocol)) {
                    cache.put(request, clone);
                  }
                });
            }
            return response;
          })
          .catch(() => {
            if (request.mode === "navigate") {
              return caches.match("/offline.html");
            }
            // Renvoyer une réponse par défaut pour les ressources
            if (request.url.match(/\.(jpg|jpeg|png|gif|svg|ico)$/)) {
              return new Response(null, { status: 404 });
            }
            return new Response(JSON.stringify({ error: 'Network error' }), {
              headers: { 'Content-Type': 'application/json' }
            });
          });
      })
  );
});


// Synchronisation
self.addEventListener("sync", (event) => {
  if (event.tag === SYNC_TAG) {
    event.waitUntil(syncCollects());
  }
});

// Écoute des messages pour la synchronisation immédiate
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SYNC_NOW') {
    console.log('📢 Demande de synchronisation immédiate reçue');
    syncCollects();
  }
});

async function syncCollects() {
  console.log("🚀 Début de la synchronisation des collectes");
  const collects = await getPendingCollects();
  console.log("� Nombre de collectes en attente:", collects.length);
  console.log("📝 Détail des collectes:", collects);

  for (const record of collects) {
    console.log("🔄 Tentative de synchronisation pour la collecte ID:", record.id);
    console.log("📄 Données à envoyer:", record.payload);
    
    // Vérification des champs requis
    const requiredFields = ['campaignId', 'siteId', 'countType', 'quality', 'method'];
    const missingFields = requiredFields.filter(field => !record.payload || !record.payload[field]);
    if (missingFields.length > 0) {
        console.warn("⚠️ Champs manquants dans le payload:", missingFields);
    }
    
    console.log("🔍 Détail des champs :");
    console.log("- campaignId:", record.payload?.campaignId);
    console.log("- siteId:", record.payload?.siteId);
    console.log("- countType:", record.payload?.countType);
    console.log("- quality:", record.payload?.quality);
    console.log("- method:", record.payload?.method);
    
    console.log("🔑 CSRF Token:", record.csrfToken ? "Présent" : "Manquant");

    try {
      const requestData = {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": record.csrfToken || ""
        },
        credentials: "include", // Changé de 'same-origin' à 'include' pour s'assurer que les cookies sont envoyés
        body: JSON.stringify(record.payload)
      };
      console.log("📨 Configuration de la requête:", requestData);

      const response = await fetch("/user/collected/data/api/collects", requestData);
      console.log("📥 Status de la réponse:", response.status);
      const responseText = await response.text();
      console.log("📫 Contenu de la réponse:", responseText);

      if (response.ok) {
        console.log("✅ Collecte synchronisée avec succès:", record.id);
        await removeCollect(record.id);
        
        // Notifier le succès
        await self.registration.showNotification("Synchronisation réussie", {
          body: "Une collecte a été synchronisée avec succès",
          icon: "/images/icon-192x192.png"
        });

        // Informer toutes les fenêtres du succès
        const clients = await self.clients.matchAll({ type: 'window' });
        clients.forEach(client => {
          client.postMessage({
            type: 'SYNC_SUCCESS',
            message: 'Collecte synchronisée avec succès',
            collectId: record.id
          });
        });
      } else {
        const errorMsg = `Échec de la synchronisation (${response.status}): ${responseText}`;
        console.warn("⚠️", errorMsg);
        
        // Informer toutes les fenêtres de l'erreur
        const clients = await self.clients.matchAll({ type: 'window' });
        clients.forEach(client => {
          client.postMessage({
            type: 'SYNC_ERROR',
            error: errorMsg,
            collectId: record.id,
            status: response.status
          });
        });
      }
    } catch (err) {
      const errorMsg = `Erreur de synchronisation: ${err.message || err}`;
      console.error("🚨", errorMsg);
      
      // Informer toutes les fenêtres de l'erreur
      const clients = await self.clients.matchAll({ type: 'window' });
      clients.forEach(client => {
        client.postMessage({
          type: 'SYNC_ERROR',
          error: errorMsg,
          collectId: record.id
        });
      });
      
      // Notifier l'erreur
      await self.registration.showNotification("Erreur de synchronisation", {
        body: errorMsg,
        icon: "/images/icon-192x192.png"
      });
    }
  }
}

// Notifications
self.addEventListener("push", (event) => {
  const data = event.data ? event.data.json() : {
    title: "Nouvelle collecte",
    body: "Une nouvelle collecte a été ajoutée."
  };
  
  const options = {
    body: data.body,
    icon: "/images/icon-192x192.png",
    badge: "/images/badge-72x72.png"
  };

  event.waitUntil(
    self.registration.showNotification(data.title, options)
  );
});

self.addEventListener("notificationclick", (event) => {
  event.notification.close();
  event.waitUntil(
    clients.matchAll({ type: "window" }).then((clientList) => {
      if (clientList.length > 0) {
        return clientList[0].focus();
      }
      return clients.openWindow("/");
    })
  );
});