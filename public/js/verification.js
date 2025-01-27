window.onload = function () {
    const transaction = db.transaction(["environmentalConditions"], "readonly");
    const store = transaction.objectStore("environmentalConditions");

    const request = store.getAll();

    request.onsuccess = function () {
        const dataTable = document.getElementById("dataTable");
        request.result.forEach((data) => {
            const row = document.createElement("tr");
            row.innerHTML = `
                <td>${data.siteCollection}</td>
                <td>${data.weatherEffect}</td>
                <td>${data.waterState}</td>
                <td>${data.quality}</td>
                <td>${data.method}</td>
                <td>${data.species.map(s => `${s.birdName} (${s.birdCount})`).join(", ")}</td>
                <td>
                    <button onclick="validateData(${data.id})">Valider</button>
                    <button onclick="deleteData(${data.id})">Rejeter</button>
                </td>
            `;
            dataTable.appendChild(row);
        });
    };
};

function validateData(id) {
    const transaction = db.transaction(["environmentalConditions"], "readonly");
    const store = transaction.objectStore("environmentalConditions");
    const request = store.get(id);

    request.onsuccess = function () {
        const data = request.result;

        // Envoyer les données au serveur pour validation finale
        fetch("/api/validate", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data),
        })
        .then((response) => response.json())
        .then(() => {
            alert("Données validées et envoyées !");
            deleteData(id);
        })
        .catch((error) => console.error("Erreur lors de la validation", error));
    };
}

function deleteData(id) {
    const transaction = db.transaction(["environmentalConditions"], "readwrite");
    const store = transaction.objectStore("environmentalConditions");

    store.delete(id);

    transaction.oncomplete = function () {
        alert("Données supprimées !");
        location.reload();
    };
}
