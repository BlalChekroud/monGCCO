// const PREFIX = "WingWatch-v1";
// const OFFLINE_URL = "/offline.html";
// const DB_NAME = "WingWatchDB";
// const STORE = "collects";

// // Installation : mettre offline.html en cache
// self.addEventListener("install", (event) => {
//   event.waitUntil(
//     (async () => {
//       const cache = await caches.open(PREFIX);
//       await cache.addAll([OFFLINE_URL, "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"]);
//     })()
//   );
//   self.skipWaiting();
//   console.log(`${PREFIX} installé`);
// });

// // Activation : nettoyer les anciens caches
// self.addEventListener("activate", (event) => {
//   event.waitUntil(
//     (async () => {
//       const keys = await caches.keys();
//       await Promise.all(keys.map((key) => {
//         if (key !== PREFIX) return caches.delete(key);
//       }));
//     })()
//   );
//   self.clients.claim();
//   console.log(`${PREFIX} activé`);
// });

// // Fetch : fournir offline.html si pas de réseau
// self.addEventListener("fetch", (event) => {
//   if (event.request.mode === "navigate") {
//     event.respondWith(
//       (async () => {
//         try {
//           return await fetch(event.request);
//         } catch (e) {
//           const cache = await caches.open(PREFIX);
//           return cache.match(OFFLINE_URL);
//         }
//       })()
//     );
//   }
// });

// // Background Sync
// self.addEventListener("sync", (event) => {
//   if (event.tag === "sync-collects") {
//     event.waitUntil(syncCollects());
//   }
// });

// // ---------- Helpers pour IndexedDB ----------
// function openDb() {
//   return new Promise((resolve, reject) => {
//     const req = indexedDB.open(DB_NAME, 1);
//     req.onupgradeneeded = () => {
//       const db = req.result;
//       if (!db.objectStoreNames.contains(STORE)) {
//         db.createObjectStore(STORE, { keyPath: "id", autoIncrement: true });
//       }
//     };
//     req.onsuccess = () => resolve(req.result);
//     req.onerror = () => reject(req.error);
//   });
// }

// // Sync vers le serveur
// async function syncCollects() {
//   const db = await openDb();
//   const tx = db.transaction(STORE, "readwrite");
//   const store = tx.objectStore(STORE);
//   const all = await store.getAll();

//   for (const item of all) {
//     try {
//       const res = await fetch("/user/collected/data/sync", {
//         method: "POST",
//         headers: {
//           "Content-Type": "application/json",
//           "X-CSRF-TOKEN": item.csrfToken || ""
//         },
//         credentials: "same-origin",
//         body: JSON.stringify(item.payload)
//       });
//       if (res.ok) {
//         store.delete(item.id); // Supprimer si bien envoyé
//       }
//     } catch (e) {
//       console.warn("Sync échoué, sera retenté :", e);
//     }
//   }
//   await tx.done;
// }


// const CACHE_NAME = "wingwatch-cache-v1";
// const METADATA_URLS = [
//   "/api/metadata/campaigns",
//   "/api/metadata/species",
//   "/api/metadata/countType",
//   "/api/metadata/quality",
//   "/api/metadata/method",
// ];
// const SYNC_TAG = "sync-collects";

// // 📌 Fonction utilitaire pour ouvrir IndexedDB
// function openDb() {
//   return new Promise((resolve, reject) => {
//     const req = indexedDB.open("WingWatchDB", 1);
//     req.onsuccess = () => resolve(req.result);
//     req.onerror = () => reject(req.error);
//   });
// }

// // 📌 Lire les collectes hors ligne dans IndexedDB
// async function getPendingCollects() {
//   const db = await openDb();
//   return new Promise((resolve) => {
//     const tx = db.transaction("collects", "readonly");
//     const store = tx.objectStore("collects");
//     const req = store.getAll();
//     req.onsuccess = () => resolve(req.result);
//     req.onerror = () => resolve([]);
//   });
// }

// // 📌 Supprimer une collecte une fois envoyée
// async function removeCollect(id) {
//   const db = await openDb();
//   return new Promise((resolve) => {
//     const tx = db.transaction("collects", "readwrite");
//     tx.objectStore("collects").delete(id);
//     tx.oncomplete = () => resolve();
//   });
// }

// // Installation du SW → précache des pages et API metadata
// self.addEventListener("install", (event) => {
//   event.waitUntil(
//     caches.open(CACHE_NAME).then((cache) =>
//       cache.addAll([
//         "/",
//         "/offline.html",
//         ...METADATA_URLS
//       ])
//     )
//   );
// });

// // Activation → nettoyage des anciens caches
// self.addEventListener("activate", (event) => {
//   event.waitUntil(
//     caches.keys().then((keys) =>
//       Promise.all(keys.map((key) => key !== CACHE_NAME && caches.delete(key)))
//     )
//   );
// });

// // Fetch handler
// self.addEventListener("fetch", (event) => {
//   const { request } = event;

//   // Si c’est un endpoint metadata
//   if (METADATA_URLS.some((url) => request.url.includes(url))) {
//     event.respondWith(
//       fetch(request)
//         .then((response) => {
//           const clone = response.clone();
//           caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
//           return response;
//         })
//         .catch(() => caches.match(request))
//     );
//     return;
//   }

//   // Stratégie cache-first pour le reste
//   event.respondWith(
//     caches.match(request).then((cached) => {
//       return (
//         cached ||
//         fetch(request).catch(() => {
//           if (request.mode === "navigate") {
//             return caches.match("/offline.html");
//           }
//         })
//       );
//     })
//   );
// });

// // 📌 Background Sync → envoie les collectes en attente
// self.addEventListener("sync", (event) => {
//   if (event.tag === SYNC_TAG) {
//     event.waitUntil(syncCollects());
//   }
// });

// async function syncCollects() {
//   const collects = await getPendingCollects();
//   console.log("🔄 Tentative de sync des collectes hors ligne :", collects);

//   for (const record of collects) {
//     try {
//       const response = await fetch("/api/collectedData", {
//         method: "POST",
//         headers: {
//           "Content-Type": "application/json",
//           "X-CSRF-TOKEN": record.csrfToken || "" // si tu en as besoin
//         },
//         body: JSON.stringify(record.payload)
//       });

//       if (response.ok) {
//         console.log("✅ Collecte envoyée :", record);
//         await removeCollect(record.id);
//       } else {
//         console.warn("⚠️ Échec sync collecte :", await response.text());
//       }
//     } catch (err) {
//       console.error("🚨 Erreur réseau pendant sync :", err);
//     }
//   }
// }
// // // 📌 Notification de succès
// // self.addEventListener("notificationclick", (event) => {
// //   event.notification.close();
// //   event.waitUntil(
// //     clients.openWindow("/").then(() => {
// //       console.log("🔔 Notification cliquée, fenêtre ouverte !");
// //     })
// //   );
// // });
// // // 📌 Notification de réception
// // self.addEventListener("push", (event) => {
// //   const data = event.data ? event.data.json() : { title: "Nouvelle collecte", body: "Une nouvelle collecte a été ajoutée." };
// //   const options = {
// //     body: data.body,
// //     icon: "/images/icon-192x192.png",
// //     badge: "/images/badge-72x72.png"
// //   };

// //   event.waitUntil(
// //     self.registration.showNotification(data.title, options)
// //   );
// // });
// // // 📌 Notification de fermeture
// // self.addEventListener("notificationclose", (event) => {
// //   console.log("🔔 Notification fermée :", event.notification);
// // });
// // // 📌 Notification de clic
// // self.addEventListener("notificationclick", (event) => {
// //   event.notification.close();
// //   event.waitUntil(
// //     clients.matchAll({ type: "window" }).then((clientList) => {
// //       if (clientList.length > 0) {
// //         return clientList[0].focus();
// //       }
// //       return clients.openWindow("/");
// //     })
// //   );
// // });
// // // 📌 Notification de synchronisation
// // self.addEventListener("sync", (event) => {
// //   if (event.tag === SYNC_TAG) {
// //     event.waitUntil(syncCollects());
// //   }
// // });
// // // 📌 Synchronisation des collectes
// // async function syncCollects() {
// //   const collects = await getPendingCollects();
// //   console.log("🔄 Tentative de synchronisation des collectes :", collects);

// //   for (const record of collects) {
// //     try {
// //       const response = await fetch("/api/collectedData", {
// //         method: "POST",
// //         headers: {
// //           "Content-Type": "application/json",
// //           "X-CSRF-TOKEN": record.csrfToken || ""
// //         },
// //         body: JSON.stringify(record.payload)
// //       });

// //       if (response.ok) {
// //         console.log("✅ Collecte envoyée :", record);
// //         await removeCollect(record.id);
// //       } else {
// //         console.warn("⚠️ Échec de la synchronisation de la collecte :", await response.text());
// //       }
// //     } catch (err) {
// //       console.error("🚨 Erreur réseau pendant la synchronisation :", err);
// //     }
// //   }
// // }


// const PREFIX = "WingWatch-v1";
// const OFFLINE_URL = "/offline.html";
// const DB_NAME = "WingWatchDB";
// const STORE = "collects";

// // Installation : mettre offline.html en cache
// self.addEventListener("install", (event) => {
//   event.waitUntil(
//     (async () => {
//       const cache = await caches.open(PREFIX);
//       await cache.addAll([OFFLINE_URL, "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"]);
//     })()
//   );
//   self.skipWaiting();
//   console.log(`${PREFIX} installé`);
// });

// // Activation : nettoyer les anciens caches
// self.addEventListener("activate", (event) => {
//   event.waitUntil(
//     (async () => {
//       const keys = await caches.keys();
//       await Promise.all(keys.map((key) => {
//         if (key !== PREFIX) return caches.delete(key);
//       }));
//     })()
//   );
//   self.clients.claim();
//   console.log(`${PREFIX} activé`);
// });

// // Fetch : fournir offline.html si pas de réseau
// self.addEventListener("fetch", (event) => {
//   if (event.request.mode === "navigate") {
//     event.respondWith(
//       (async () => {
//         try {
//           return await fetch(event.request);
//         } catch (e) {
//           const cache = await caches.open(PREFIX);
//           return cache.match(OFFLINE_URL);
//         }
//       })()
//     );
//   }
// });

// // Background Sync
// self.addEventListener("sync", (event) => {
//   if (event.tag === "sync-collects") {
//     event.waitUntil(syncCollects());
//   }
// });

// // ---------- Helpers pour IndexedDB ----------
// function openDb() {
//   return new Promise((resolve, reject) => {
//     const req = indexedDB.open(DB_NAME, 1);
//     req.onupgradeneeded = () => {
//       const db = req.result;
//       if (!db.objectStoreNames.contains(STORE)) {
//         db.createObjectStore(STORE, { keyPath: "id", autoIncrement: true });
//       }
//     };
//     req.onsuccess = () => resolve(req.result);
//     req.onerror = () => reject(req.error);
//   });
// }

// // Sync vers le serveur
// async function syncCollects() {
//   const db = await openDb();
//   const tx = db.transaction(STORE, "readwrite");
//   const store = tx.objectStore(STORE);
//   const all = await store.getAll();

//   for (const item of all) {
//     try {
//       const res = await fetch("/user/collected/data/sync", {
//         method: "POST",
//         headers: {
//           "Content-Type": "application/json",
//           "X-CSRF-TOKEN": item.csrfToken || ""
//         },
//         credentials: "same-origin",
//         body: JSON.stringify(item.payload)
//       });
//       if (res.ok) {
//         store.delete(item.id); // Supprimer si bien envoyé
//       }
//     } catch (e) {
//       console.warn("Sync échoué, sera retenté :", e);
//     }
//   }
//   await tx.done;
// }


const CACHE_NAME = "wingwatch-cache-v1";
const METADATA_URLS = [
  "/api/metadata/campaigns",
  "/api/metadata/species",
  "/api/metadata/countType",
  "/api/metadata/quality",
  "/api/metadata/method",
];
const SYNC_TAG = "sync-collects";

// 📌 Fonction utilitaire pour ouvrir IndexedDB
function openDb() {
  return new Promise((resolve, reject) => {
    const req = indexedDB.open("WingWatchDB", 1);
    req.onsuccess = () => resolve(req.result);
    req.onerror = () => reject(req.error);
  });
}

// 📌 Lire les collectes hors ligne dans IndexedDB
async function getPendingCollects() {
  const db = await openDb();
  return new Promise((resolve) => {
    const tx = db.transaction("collects", "readonly");
    const store = tx.objectStore("collects");
    const req = store.getAll();
    req.onsuccess = () => resolve(req.result);
    req.onerror = () => resolve([]);
  });
}

// 📌 Supprimer une collecte une fois envoyée
async function removeCollect(id) {
  const db = await openDb();
  return new Promise((resolve) => {
    const tx = db.transaction("collects", "readwrite");
    tx.objectStore("collects").delete(id);
    tx.oncomplete = () => resolve();
  });
}

// Installation du SW → précache des pages et API metadata
self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) =>
      cache.addAll([
        "/",
        "/offline.html",
        ...METADATA_URLS
      ])
    )
  );
});

// Activation → nettoyage des anciens caches
self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.map((key) => key !== CACHE_NAME && caches.delete(key)))
    )
  );
});

// Fetch handler
self.addEventListener("fetch", (event) => {
  const { request } = event;

  // Si c’est un endpoint metadata
  if (METADATA_URLS.some((url) => request.url.includes(url))) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          const clone = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
          return response;
        })
        .catch(() => caches.match(request))
    );
    return;
  }

  // Stratégie cache-first pour le reste
  event.respondWith(
    caches.match(request).then((cached) => {
      return (
        cached ||
        fetch(request).catch(() => {
          if (request.mode === "navigate") {
            return caches.match("/offline.html");
          }
        })
      );
    })
  );
});

// 📌 Background Sync → envoie les collectes en attente
self.addEventListener("sync", (event) => {
  if (event.tag === SYNC_TAG) {
    event.waitUntil(syncCollects());
  }
});

async function syncCollects() {
  const collects = await getPendingCollects();
  console.log("🔄 Tentative de sync des collectes hors ligne :", collects);

  for (const record of collects) {
    try {
      const response = await fetch("/user/collected/data/api/collects", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": record.csrfToken || "" // si tu en as besoin
        },
        body: JSON.stringify(record.payload)
      });

      if (response.ok) {
        console.log("✅ Collecte envoyée :", record);
        await removeCollect(record.id);
      } else {
        console.warn("⚠️ Échec sync collecte :", await response.text());
      }
    } catch (err) {
      console.error("🚨 Erreur réseau pendant sync :", err);
    }
  }
}
// 📌 Notification de succès
self.addEventListener("notificationclick", (event) => {
  event.notification.close();
  event.waitUntil(
    clients.openWindow("/").then(() => {
      console.log("🔔 Notification cliquée, fenêtre ouverte !");
    })
  );
});
// 📌 Notification de réception
self.addEventListener("push", (event) => {
  const data = event.data ? event.data.json() : { title: "Nouvelle collecte", body: "Une nouvelle collecte a été ajoutée." };
  const options = {
    body: data.body,
    icon: "/images/icon-192x192.png",
    badge: "/images/badge-72x72.png"
  };

  event.waitUntil(
    self.registration.showNotification(data.title, options)
  );
});
// 📌 Notification de fermeture
self.addEventListener("notificationclose", (event) => {
  console.log("🔔 Notification fermée :", event.notification);
});
// 📌 Notification de clic
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
// 📌 Notification de synchronisation
self.addEventListener("sync", (event) => {
  if (event.tag === SYNC_TAG) {
    event.waitUntil(syncCollects());
  }
});
// 📌 Synchronisation des collectes
async function syncCollects() {
  const collects = await getPendingCollects();
  console.log("🔄 Tentative de synchronisation des collectes :", collects);

  for (const record of collects) {
    try {
      const response = await fetch("/user/collected/data/api/collects", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": record.csrfToken || ""
        },
        body: JSON.stringify(record.payload)
      });

      if (response.ok) {
        console.log("✅ Collecte envoyée :", record);
        await removeCollect(record.id);
      } else {
        console.warn("⚠️ Échec de la synchronisation de la collecte :", await response.text());
      }
    } catch (err) {
      console.error("🚨 Erreur réseau pendant la synchronisation :", err);
    }
  }
}

// const CACHE_NAME = "wingwatch-cache-v1";
// const OFFLINE_URL = "/offline.html";
// const API_COLLECTS = "/user/collected/data/api/collects";

// const urlsToCache = [
//   "/",
//   "/offline.html",
//   "/api/metadata/campaigns",
//   "/api/metadata/species",
//   "/api/metadata/countType",
//   "/api/metadata/quality",
//   "/api/metadata/method",
//   "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css",
// ];
// // Installation du SW
// self.addEventListener("install", (event) => {
//   event.waitUntil(
//     caches.open(CACHE_NAME).then(async (cache) => {
//       for (const url of urlsToCache) {
//         try {
//           await cache.add(url);
//           console.log("✅ Cached:", url);
//         } catch (e) {
//           console.warn("⚠️ Impossible de mettre en cache:", url, e);
//         }
//       }
//     })
//   );
// });

// // Fichiers statiques à mettre en cache dès l'installation
// // const STATIC_ASSETS = [
// //   "/",
// //   "/offline.html",
// //   "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css",
// // ];

// // Lors de l'installation → on met en cache les fichiers essentiels
// // self.addEventListener("install", (event) => {
// //   event.waitUntil(
// //     caches.open(CACHE_NAME).then((cache) => {
// //       return cache.addAll(STATIC_ASSETS);
// //     })
// //   );
// //   console.log("✅ Service Worker installé et fichiers statiques mis en cache");
// // });

// // Activation → nettoyage des anciens caches
// self.addEventListener("activate", (event) => {
//   event.waitUntil(
//     caches.keys().then((keys) =>
//       Promise.all(
//         keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
//       )
//     )
//   );
//   console.log("♻️ Service Worker activé, anciens caches nettoyés");
// });

// // Intercepter les requêtes
// self.addEventListener("fetch", (event) => {
//   const url = new URL(event.request.url);

//   // Si c'est une requête vers ton API
//   if (url.pathname.startsWith("/api/")) {
//     event.respondWith(
//       fetch(event.request) // d’abord réseau
//         .then((response) => {
//           // clone et stocke la réponse en cache
//           const clone = response.clone();
//           caches.open(CACHE_NAME).then((cache) => {
//             cache.put(event.request, clone);
//           });
//           return response;
//         })
//         .catch(() => {
//           // si échec → cherche en cache
//           return caches.match(event.request).then((res) => {
//             return (
//               res ||
//               new Response(
//                 JSON.stringify({ error: "⚠️ Données API indisponibles hors ligne" }),
//                 { headers: { "Content-Type": "application/json" } }
//               )
//             );
//           });
//         })
//     );
//   } else {
//     // Pour les autres requêtes (HTML, CSS, JS, images…)
//     event.respondWith(
//       fetch(event.request).catch(() => {
//         return caches.match(event.request).then((res) => {
//           return res || caches.match("/offline.html");
//         });
//       })
//     );
//   }
// });
