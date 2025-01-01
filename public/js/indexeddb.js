class IndexedDBService {
    constructor(dbName, version = 1) {
        this.dbName = dbName;
        this.version = version;
    }

    async openDB(entityNames = []) {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.version);

            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                entityNames.forEach((entity) => {
                    if (!db.objectStoreNames.contains(entity)) {
                        db.createObjectStore(entity, { keyPath: "id" });
                    }
                });
            };

            request.onsuccess = () => resolve(request.result);
            request.onerror = (event) => reject(event.target.error);
        });
    }

    async save(entityName, data) {
        const db = await this.openDB([entityName]);

        return new Promise((resolve, reject) => {
            const transaction = db.transaction(entityName, "readwrite");
            const store = transaction.objectStore(entityName);

            const request = store.put(data);

            request.onsuccess = () => resolve(true);
            request.onerror = (event) => reject(event.target.error);
        });
    }

    async getAll(entityName) {
        const db = await this.openDB([entityName]);

        return new Promise((resolve, reject) => {
            const transaction = db.transaction(entityName, "readonly");
            const store = transaction.objectStore(entityName);

            const request = store.getAll();

            request.onsuccess = () => resolve(request.result);
            request.onerror = (event) => reject(event.target.error);
        });
    }

    async delete(entityName, id) {
        const db = await this.openDB([entityName]);

        return new Promise((resolve, reject) => {
            const transaction = db.transaction(entityName, "readwrite");
            const store = transaction.objectStore(entityName);

            const request = store.delete(id);

            request.onsuccess = () => resolve(true);
            request.onerror = (event) => reject(event.target.error);
        });
    }

    async clear(entityName) {
        const db = await this.openDB([entityName]);

        return new Promise((resolve, reject) => {
            const transaction = db.transaction(entityName, "readwrite");
            const store = transaction.objectStore(entityName);

            const request = store.clear();

            request.onsuccess = () => resolve(true);
            request.onerror = (event) => reject(event.target.error);
        });
    }
}

export default IndexedDBService;
