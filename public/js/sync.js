// Ce fichier gère la synchronisation des données locales avec le serveur lorsqu'une connexion est disponible.

window.addEventListener("online", function () {
    const transaction = db.transaction(["environmentalConditions"], "readonly");
    const store = transaction.objectStore("environmentalConditions");

    const request = store.getAll();

    request.onsuccess = function () {
        const offlineData = request.result;
        if (offlineData.length > 0) {
            // Envoyer les données au serveur
            fetch("/api/sync", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(offlineData),
            })
            .then((response) => response.json())
            .then(() => {
                // Effacer les données locales après synchronisation
                const deleteTransaction = db.transaction(["environmentalConditions"], "readwrite");
                const deleteStore = deleteTransaction.objectStore("environmentalConditions");
                deleteStore.clear();
                alert("Données synchronisées avec succès !");
            })
            .catch((error) => console.error("Erreur de synchronisation", error));
        }
    };
});
