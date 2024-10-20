document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("exportCSVButton").addEventListener("click", function () {
        var table = document.querySelector(".table");
        var csv = [];
        var rows = table.querySelectorAll("tr");

        // Parcourir chaque ligne du tableau
        rows.forEach(function (row) {
            var cols = row.querySelectorAll("td, th");
            var rowData = [];
            cols.forEach(function (col) {
                rowData.push('"' + col.innerText.replace(/"/g, '""') + '"');  // Échapper les guillemets doubles
            });
            csv.push(rowData.join(",")); // Joindre les colonnes avec des virgules
        });

        // Générer un fichier CSV
        var csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
        var downloadLink = document.createElement("a");
        downloadLink.download = "campagnes.csv";  // Nom du fichier exporté
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = "none";
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    });
});