// document.addEventListener("DOMContentLoaded", function() {
//     // Texte à afficher
//         /* Page Inscrire */

//         const text = "Gestion des campagnes de comptage des oiseaux marins";
        
//         // Sélection de l'élément où le texte va apparaître
//         const element = document.getElementById('dynamic-text');
        
//         // Variables pour contrôler l'affichage et la suppression des caractères
//         let index = 0;
//         let deleting = false;
        
//         // Fonction pour afficher et cacher les caractères un par un
//         function displayNextChar() {
//             if (!deleting) {
//                 // Ajouter les caractères un par un
//                 if (index < text.length) {
//                     element.textContent += text.charAt(index);
//                     index++;
//                     setTimeout(displayNextChar, 100); // Ajustez la vitesse ici
//                 } else {
//                     // Une fois le texte complètement affiché, attendre avant de commencer à effacer
//                     setTimeout(() => {
//                         deleting = true;
//                         displayNextChar();
//                     }, 1000); // Attendre 1 seconde avant d'effacer
//                 }
//             } else {
//                 // Supprimer les caractères un par un
//                 if (index > 0) {
//                     element.textContent = text.substring(0, index - 1);
//                     index--;
//                     setTimeout(displayNextChar, 100); // Ajustez la vitesse ici
//                 } else {
//                     // Une fois que tout est effacé, recommencer l'affichage
//                     deleting = false;
//                     setTimeout(displayNextChar, 1000); // Attendre 1 seconde avant de recommencer
//                 }
//             }
//         }
        
//         // Démarrer l'animation
//         window.onload = function() {
//             displayNextChar();
//         };
// });

document.addEventListener("DOMContentLoaded", function() {
    // Textes à afficher
    const texts = {
        fr: "Gestion des campagnes de comptage des oiseaux marins",
        en: "Management of Marine Bird Counting Campaigns"
    };

    // Sélection de l'élément où le texte va apparaître
    const element = document.getElementById('dynamic-text');

    // Variables pour contrôler l'affichage et la suppression des caractères
    let index = 0;
    let deleting = false;
    let currentLocale = 'fr'; // Langue par défaut

    // Fonction pour afficher le texte
    function displayText(text) {
        element.textContent = ''; // Réinitialiser le texte
        index = 0;
        deleting = false;
        displayNextChar(text); // Commencer à afficher le texte
    }

    // Fonction pour afficher et cacher les caractères un par un
    function displayNextChar(text) {
        if (!deleting) {
            // Ajouter les caractères un par un
            if (index < text.length) {
                element.textContent += text.charAt(index);
                index++;
                setTimeout(() => displayNextChar(text), 100); // Ajustez la vitesse ici
            } else {
                // Une fois le texte complètement affiché, attendre avant de commencer à effacer
                setTimeout(() => {
                    deleting = true;
                    displayNextChar(text);
                }, 1000); // Attendre 1 seconde avant d'effacer
            }
        } else {
            // Supprimer les caractères un par un
            if (index > 0) {
                element.textContent = text.substring(0, index - 1);
                index--;
                setTimeout(() => displayNextChar(text), 100); // Ajustez la vitesse ici
            } else {
                // Une fois que tout est effacé, recommencer l'affichage
                deleting = false;
                setTimeout(() => displayText(texts[currentLocale]), 1000); // Attendre 1 seconde avant de recommencer
            }
        }
    }

    // Démarrer l'animation avec le texte par défaut
    displayText(texts[currentLocale]);

    // Écouter les changements de langue
    document.getElementById('language-switcher').addEventListener('change', function(event) {
        currentLocale = event.target.value; // Mettre à jour la langue actuelle
        displayText(texts[currentLocale]); // Afficher le texte correspondant à la nouvelle langue
    });
});

