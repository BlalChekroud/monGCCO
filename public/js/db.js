// Ce fichier contient le code pour initialiser la base de données IndexedDB et gérer les transactions de lecture/écriture.

let db;
const request = indexedDB.open("OfflineDataDB", 1);

request.onupgradeneeded = function (event) {
    db = event.target.result;
    if (!db.objectStoreNames.contains("environmentalConditions")) {
        db.createObjectStore("environmentalConditions", { keyPath: "id", autoIncrement: true });
    }
};

request.onsuccess = function (event) {
    db = event.target.result;
    console.log("Database initialized");
};

request.onerror = function (event) {
    console.error("Error initializing database", event.target.error);
};
