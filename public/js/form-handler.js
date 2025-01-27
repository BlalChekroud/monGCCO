// Ce fichier contient le code pour capturer les données du formulaire et les enregistrer dans IndexedDB.

document.querySelector("form").addEventListener("submit", function (event) {
    event.preventDefault();

    const formData = {
        id: Date.now(), // Identifiant unique
        siteCollection: document.getElementById("siteCollection").value,
        weatherEffect: document.getElementById("weatherEffect").value,
        waterState: document.getElementById("waterState").value,
        quality: document.getElementById("quality").value,
        method: document.getElementById("method").value,
        species: Array.from(document.querySelectorAll(".specy-group")).map((group) => ({
            birdName: group.querySelector(".specy").value,
            birdCount: parseInt(group.querySelector(".count").value, 10),
        })),
    };

    const transaction = db.transaction(["environmentalConditions"], "readwrite");
    const store = transaction.objectStore("environmentalConditions");

    store.add(formData);

    transaction.oncomplete = function () {
        alert("Données enregistrées hors ligne pour vérification !");
    };

    transaction.onerror = function (event) {
        console.error("Erreur lors de l'enregistrement des données", event.target.error);
    };
});
