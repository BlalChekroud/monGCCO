import IndexedDBService from './indexeddb.js';

const dbService = new IndexedDBService("WingWatchDB");

export const syncData = async () => {
    const entities = ["Campaign", "BirdSpecies", "CollectedData"]; // Liste des entités à synchroniser

    for (const entity of entities) {
        const localData = await dbService.getAll(entity);

        if (localData.length > 0) {
            fetch(`/api/sync/${entity.toLowerCase()}`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(localData),
            })
                .then((response) => response.json())
                .then((data) => {
                    console.log(`${entity} synchronisé :`, data);

                    // Nettoyer les données locales après la synchronisation
                    dbService.clear(entity);
                })
                .catch((err) => console.error(`Erreur de synchronisation pour ${entity} :`, err));
        }
    }
};

// Déclencher la synchronisation en ligne
window.addEventListener("online", syncData);
