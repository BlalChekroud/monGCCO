import IndexedDBService from './indexeddb.js';

// Initialiser le service
const dbService = new IndexedDBService("WingWatchDB");

// Ajouter ou mettre à jour une entité
dbService
    .save("Campaign", { id: 1, name: "Campagne 1", description: "Description" })
    .then(() => console.log("Campagne enregistrée."))
    .catch((err) => console.error("Erreur :", err));

// Récupérer toutes les campagnes
dbService
    .getAll("Campaign")
    .then((data) => console.log("Campagnes :", data))
    .catch((err) => console.error("Erreur :", err));

// Supprimer une campagne
dbService
    .delete("Campaign", 1)
    .then(() => console.log("Campagne supprimée."))
    .catch((err) => console.error("Erreur :", err));
