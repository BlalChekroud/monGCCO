(function() {
document.addEventListener("DOMContentLoaded", function() {
    
    /* cacher les messages après quelques secondes */
    document.addEventListener('DOMContentLoaded', function() {
        console.log("DOM fully loaded and parsed");
        
        setTimeout(function() {
            const flashMessages = document.querySelectorAll('.alertFlash');
            flashMessages.forEach(function(message) {
                message.style.display = 'none';
                console.log("Hiding message: ", message);
            });
        }, 5000); // 5000 milliseconds = 5 seconds
    });


/* CLOCK */
function showDateTime() {
    const date = new Date();
    let hours = date.getHours();
    let minutes = date.getMinutes();
    let seconds = date.getSeconds();
  
    const day = date.getDate();
    const month = date.getMonth() + 1;
    const year = date.getFullYear();
  
    // Format time with leading zeros
    hours = hours < 10 ? '0' + hours : hours;
    minutes = minutes < 10 ? '0' + minutes : minutes;
    seconds = seconds < 10 ? '0' + seconds : seconds;
  
    const timeString = `${hours}:${minutes}:${seconds}`;
    const dateTime = `${day}/${month}/${year}`;
    
    const clockElement = document.getElementById("clock");
    if (clockElement) {
      clockElement.innerText = dateTime + ' ' + timeString;
    }
  }
  
  showDateTime();
  setInterval(showDateTime, 1000); // Mettre à jour toutes les secondes
  
    
  /* eyes */
// document.addEventListener("DOMContentLoaded", function() {

//     feather.replace();
//     const eye = document.querySelector(".feather-eye");
//     const eyeoff = document.querySelector(".feather-eye-off");
//     const passwordField = document.querySelector("input[type=password]");

//     eye.addEventListener("click", () => {
//     eye.style.display = "none";
//     eyeoff.style.display = "block";
//     passwordField.type = "text";
//     });

//     eyeoff.addEventListener("click", () => {
//     eyeoff.style.display = "none";
//     eye.style.display = "block";
//     passwordField.type = "password";
//     });
// });


  /* Imprimer */
document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("printButton").addEventListener("click", function() {
        window.print();
    });
});

  
/* Buttons d'ajout */

document.addEventListener('DOMContentLoaded', function() {
    // Gestion du formulaire Coverage
    document.querySelector('#coverageForm').addEventListener('submit', function(event) {
        event.preventDefault(); // Empêcher la soumission du formulaire

        var formData = new FormData(document.querySelector('#coverageForm'));

        fetch('/coverage/new/ajax', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Fermer la fenêtre modale
                var coverageModal = bootstrap.Modal.getInstance(document.getElementById('coverageModal'));
                coverageModal.hide();

                // Mettre à jour la liste des couvertures
                updateCoverageList();
            } else {
                console.error('Error adding coverage:', data.errors);
            }
        })
        .catch(error => console.error('Error:', error));
    });

    function updateCoverageList() {
        fetch('/coverage/list') // Modifier cette URL en fonction de votre route pour récupérer la liste des couvertures
        .then(response => response.json())
        .then(data => {
            var coverageDropdown = document.getElementById('coverageDropdown'); // Modifier cet ID en fonction de votre dropdown
            coverageDropdown.innerHTML = ''; // Vider la liste actuelle

            // Ajouter les nouvelles options
            data.coverages.forEach(coverage => {
                var option = document.createElement('option');
                option.value = coverage.id;
                option.textContent = coverage.label;
                coverageDropdown.appendChild(option);
            });
        })
        .catch(error => console.error('Error updating coverage list:', error));
    }

    // Gestion du formulaire BirdLifeTaxTreat
    document.querySelector('#birdLifeTaxTreatForm').addEventListener('submit', function(event) {
        event.preventDefault(); // Empêcher la soumission du formulaire

        var formData = new FormData(document.querySelector('#birdLifeTaxTreatForm'));

        fetch('/bird/life/tax/treat/new/ajax', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Fermer la fenêtre modale
                var birdLifeTaxTreatModal = bootstrap.Modal.getInstance(document.getElementById('birdLifeTaxTreatModal'));
                birdLifeTaxTreatModal.hide();

                // Mettre à jour la liste des tax treatments
                updateBirdLifeTaxTreatList();
            } else {
                console.error('Error adding birdLifeTaxTreat:', data.errors);
            }
        })
        .catch(error => console.error('Error:', error));
    });

    function updateBirdLifeTaxTreatList() {
        fetch('/bird/life/tax/treat/list') // Modifier cette URL en fonction de votre route pour récupérer la liste des tax treatments
        .then(response => response.json())
        .then(data => {
            var birdLifeTaxTreatDropdown = document.getElementById('birdLifeTaxTreatDropdown'); // Modifier cet ID en fonction de votre dropdown
            birdLifeTaxTreatDropdown.innerHTML = ''; // Vider la liste actuelle

            // Ajouter les nouvelles options
            data.taxTreatments.forEach(taxTreatment => {
                var option = document.createElement('option');
                option.value = taxTreatment.id;
                option.textContent = taxTreatment.label;
                birdLifeTaxTreatDropdown.appendChild(option);
            });
        })
        .catch(error => console.error('Error updating birdLifeTaxTreat list:', error));
    }

    // Gestion du formulaire IUCN Red List Category
    document.querySelector('#iucnRedListCategoryForm').addEventListener('submit', function(event) {
        event.preventDefault(); // Empêcher la soumission du formulaire

        var formData = new FormData(document.querySelector('#iucnRedListCategoryForm'));

        fetch('/iucn/red/list/category/new/ajax', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Fermer la fenêtre modale
                var iucnRedListCategoryModal = bootstrap.Modal.getInstance(document.getElementById('iucnRedListCategoryModal'));
                iucnRedListCategoryModal.hide();

                // Mettre à jour la liste des catégories IUCN
                updateIucnRedListCategoryList();
            } else {
                console.error('Error adding IUCN Red List Category:', data.errors);
            }
        })
        .catch(error => console.error('Error:', error));
    });

    function updateIucnRedListCategoryList() {
        fetch('/iucn/red/list/category/list') // Modifier cette URL en fonction de votre route pour récupérer la liste des catégories IUCN
        .then(response => response.json())
        .then(data => {
            var iucnRedListCategoryDropdown = document.getElementById('iucnRedListCategoryDropdown'); // Modifier cet ID en fonction de votre dropdown
            iucnRedListCategoryDropdown.innerHTML = ''; // Vider la liste actuelle

            // Ajouter les nouvelles options
            data.iucnRedListCategorys.forEach(iucnRedListCategory => {
                var option = document.createElement('option');
                option.value = iucnRedListCategory.id;
                option.textContent = iucnRedListCategory.label;
                iucnRedListCategoryDropdown.appendChild(option);
            });
        })
        .catch(error => console.error('Error updating IUCN Red List Category list:', error));
    }
});

});

/* Test*/
// let pseudo = false;

// document.querySelector("#coverage_label").addEventListener("input", chechPseudo);

// function chechPseudo(){
//     pseudo = this.value.length > 2;
//     console.log(pseudo);
// }

})();