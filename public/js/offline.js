// alert("Hello im on");
// if ('serviceWorker' in navigator) {
//     navigator.serviceWorker.register('../service-worker.js')
//         .then(reg => console.log("Service Worker enregistré", reg))
//         .catch(err => console.log("Erreur Service Worker", err));
// }

// if(!navigator.onLine) {
//     alert("Website Is Offline");
// }

// // Install Btn
// let installBtn = document.getElementById('install');
// window.addEventListener('beforeinstallprompt', (installEvent) => {
//     installEvent.preventDefault();
//     installBtn.style.display= "block";
//     deferredPrompt = installEvent;
// })

// installBtn.addEventListener('click', () => {
//     if(deferredPrompt) {
//         deferredPrompt.prompt();
//         deferredPrompt.userChoice.then((choiceResult) => {

//             if(choiceResult.outcome === 'accepted') {
//                 console.log('User Accepted Installing');
//                 installBtn.style.display = 'none';
//             } else {
//                 console.log('User Refused Installing');
//             }
//         })
//     }
// })

if (!navigator.onLine) {
    const offlineBanner = document.createElement('div');
    offlineBanner.innerText = 'Vous êtes hors ligne. Certaines fonctionnalités peuvent être limitées.';
    offlineBanner.style.cssText = 'position: fixed; top: 0; width: 100%; background: red; color: white; text-align: center; z-index: 1000;';
    document.body.appendChild(offlineBanner);
}

// Bouton d'installation
let deferredPrompt;

window.addEventListener('beforeinstallprompt', (installEvent) => {
    installEvent.preventDefault();
    deferredPrompt = installEvent;
    document.getElementById('install').style.display = 'block'; // Montre le bouton

    document.getElementById('install').addEventListener('click', () => {
        deferredPrompt.prompt(); // Affiche la bannière
        deferredPrompt.userChoice.then((choiceResult) => {
            if (choiceResult.outcome === 'accepted') {
                console.log('Installation acceptée');
            } else {
                console.log('Installation refusée');
            }
            deferredPrompt = null; // Réinitialise
        });
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const installBtn = document.getElementById('install');
    if (installBtn) {
        installBtn.addEventListener('click', () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('L\'utilisateur a accepté l\'installation');
                    } else {
                        console.log('L\'utilisateur a refusé l\'installation');
                    }
                    deferredPrompt = null;
                });
            }
        });
    } else {
        console.warn('Bouton d\'installation introuvable dans le DOM');
    }
});

