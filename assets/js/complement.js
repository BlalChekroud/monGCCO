(function() {
    document.addEventListener("DOMContentLoaded", function() {
        console.log("DOM fully loaded and parsed");

        // CLOCK
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

        // Imprimer
        const printButton = document.getElementById("printButton");
        if (printButton) {
            printButton.addEventListener("click", function() {
                window.print();
            });
        }

        // Buttons d'ajout
        const coverageForm = document.querySelector('#coverageForm');
        if (coverageForm) {
            coverageForm.addEventListener('submit', function(event) {
                event.preventDefault(); // Empêcher la soumission du formulaire

                var formData = new FormData(coverageForm);

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
        }

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

        const birdLifeTaxTreatForm = document.querySelector('#birdLifeTaxTreatForm');
        if (birdLifeTaxTreatForm) {
            birdLifeTaxTreatForm.addEventListener('submit', function(event) {
                event.preventDefault(); // Empêcher la soumission du formulaire

                var formData = new FormData(birdLifeTaxTreatForm);

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
        }

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

        const iucnRedListCategoryForm = document.querySelector('#iucnRedListCategoryForm');
        if (iucnRedListCategoryForm) {
            iucnRedListCategoryForm.addEventListener('submit', function(event) {
                event.preventDefault(); // Empêcher la soumission du formulaire

                var formData = new FormData(iucnRedListCategoryForm);

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
        }

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

        // const groupMemberField = document.getElementById('agents_group_groupMember');
        // const groupLeaderField = document.getElementById('agents_group_leader');
    
        // if (groupMemberField && groupLeaderField) {
        //     groupMemberField.addEventListener('change', function () {
        //         const selectedMembers = Array.from(groupMemberField.querySelectorAll('input[type="checkbox"]:checked')).map(cb => cb.value);
    
        //         // Clear existing options
        //         groupLeaderField.innerHTML = '<option value="">-- Choisir le chef du groupe --</option>';
    
        //         // Add new options
        //         selectedMembers.forEach(member => {
        //             const option = document.createElement('option');
        //             option.value = member;
        //             option.textContent = groupMemberField.querySelector(`label[for="agents_group_groupMember_${member}"]`).textContent.trim();
        //             groupLeaderField.appendChild(option);
        //         });
        //     });
        // }

        /* Site agents group */
        // var addButton = document.getElementById('add-site-agents-group');
        // var collectionHolder = document.getElementById('site-agents-group-collection');

        // // Initial index for the new items (based on the current number of elements)
        // var index = collectionHolder.children.length;

        // addButton.addEventListener('click', function () {
        //     // Get the data-prototype stored in the collection holder
        //     var prototype = collectionHolder.getAttribute('data-prototype');

        //     // Replace __name__ with the index in the prototype's HTML to make it unique
        //     var newForm = prototype.replace(/__name__/g, index);

        //     // Create a new div to contain the form and insert it into the collection holder
        //     var formDiv = document.createElement('div');
        //     formDiv.innerHTML = newForm;
        //     collectionHolder.appendChild(formDiv);

        //     // Increment the index for the next item
        //     index++;
        // });
        /* End Site agents group */

    });
})();
