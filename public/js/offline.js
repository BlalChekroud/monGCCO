// alert("Hello im on");
// if ('serviceWorker' in navigator) {
//     navigator.serviceWorker.register('../service-worker.js')
//         .then(reg => console.log("Service Worker enregistré", reg))
//         .catch(err => console.log("Erreur Service Worker", err));
// }

if(!navigator.onLine) {
    alert("Website Is Offline");
}

// Install Btn
let installBtn = document.getElementById('install');
window.addEventListener('beforeinstallprompt', (installEvent) => {
    installEvent.preventDefault();
    installBtn.style.display= "block";
    deferredPrompt = installEvent;
})

installBtn.addEventListener('click', () => {
    if(deferredPrompt) {
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then((choiceResult) => {

            if(choiceResult.outcome === 'accepted') {
                console.log('User Accepted Installing');
                installBtn.style.display = 'none';
            } else {
                console.log('User Refused Installing');
            }
        })
    }
})