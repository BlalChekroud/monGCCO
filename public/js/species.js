document.addEventListener('DOMContentLoaded', function () {

    const formCoverage = document.getElementById('coverageForm');

    formCoverage.addEventListener('submit', function (e) {
        // e.preventDefault();
        
        const formDataCoverage = new FormData(formCoverage);

        fetch('/user/coverage/new/ajax', {
            method: 'POST',
            body: formDataCoverage,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                //alert('Catégorie ajoutée : ' + data.coverage.label);

                // Optionnel : ajouter la nouvelle option dans un <select> dynamiquement
                const select = document.querySelector('#coverage_select');
                if (select) {
                    const option = document.createElement('option');
                    option.value = data.coverage.id;
                    option.text = data.coverage.label;
                    select.appendChild(option);
                    select.value = data.coverage.id;
                }

                // Fermer la modale
                const modal = bootstrap.Modal.getInstance(document.getElementById('coverageModal'));
                modal.hide();

                // Réinitialiser le formulaire
                formCoverage.reset();
            } else {
                alert('Erreur : ' + data.errors);
            }
        })
        .catch(error => {
            console.error('Erreur AJAX :', error);
        });
    });

    const formBirdLifeTaxTreat = document.getElementById('birdLifeTaxTreatForm');

    formBirdLifeTaxTreat.addEventListener('submit', function (e) {
        // e.preventDefault();
        
        const formDataBltt = new FormData(formBirdLifeTaxTreat);

        fetch('/user/bird/life/tax/treat/new/ajax', {
            method: 'POST',
            body: formDataBltt,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                //alert('Catégorie ajoutée : ' + data.birdLifeTaxTreat.label);

                // Optionnel : ajouter la nouvelle option dans un <select> dynamiquement
                const select = document.querySelector('#birdLifeTaxTreat_select');
                if (select) {
                    const option = document.createElement('option');
                    option.value = data.birdLifeTaxTreat.id;
                    option.text = data.birdLifeTaxTreat.label;
                    select.appendChild(option);
                    select.value = data.birdLifeTaxTreat.id;
                }

                // Fermer la modale
                const modal = bootstrap.Modal.getInstance(document.getElementById('birdLifeTaxTreatModal'));
                modal.hide();

                // Réinitialiser le formulaire
                formBirdLifeTaxTreat.reset();
            } else {
                alert('Erreur : ' + data.errors);
            }
        })
        .catch(error => {
            console.error('Erreur AJAX :', error);
        });
    });


    const formIUCN = document.getElementById('iucnRedListCategoryForm');

    formIUCN.addEventListener('submit', function (e) {
        // e.preventDefault();
        
        const formData = new FormData(formIUCN);

        fetch('/user/iucn/red/list/category/new/ajax', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                //alert('Catégorie ajoutée : ' + data.iucnRedListCategory.label);

                // Optionnel : ajouter la nouvelle option dans un <select> dynamiquement
                const select = document.querySelector('#iucn_category_select');
                if (select) {
                    const option = document.createElement('option');
                    option.value = data.iucnRedListCategory.id;
                    option.text = data.iucnRedListCategory.label;
                    select.appendChild(option);
                    select.value = data.iucnRedListCategory.id;
                }

                // Fermer la modale
                const modal = bootstrap.Modal.getInstance(document.getElementById('iucnRedListCategoryModal'));
                modal.hide();

                // Réinitialiser le formulaire
                formIUCN.reset();
            } else {
                alert('Erreur : ' + data.errors);
            }
        })
        .catch(error => {
            console.error('Erreur AJAX :', error);
        });
    });
});