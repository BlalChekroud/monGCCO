document.addEventListener('DOMContentLoaded', function() {
  // Fonction pour afficher les messages à l'utilisateur
  function showMessage(title, message, type) {
    console.log(`${type}:`, message);
    try {
      if (window.Swal) {
        Swal.fire({
          title: title,
          text: message,
          icon: type,
          confirmButtonText: 'OK'
        });
      } else {
        alert(`${title}: ${message}`);
      }
    } catch (error) {
      console.error('Erreur lors de l\'affichage du message:', error);
      alert(`${title}: ${message}`);
    }
  }

  // Écouteur pour les messages du Service Worker
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.addEventListener('message', event => {
      const data = event.data;
      
      switch (data.type) {
        case 'SYNC_ERROR':
          showMessage(
            'Erreur de synchronisation',
            data.error,
            'error'
          );
          break;
          
        case 'SYNC_SUCCESS':
          showMessage(
            'Succès',
            data.message,
            'success'
          );
          
          // Recharger la page si nous sommes sur la page des collectes
          if (window.location.pathname.includes('/collected/data')) {
            setTimeout(() => {
              window.location.reload();
            }, 1500);
          }
          break;
      }
    });

    // Vérifier s'il y a des collectes à synchroniser lors des changements de connectivité
    window.addEventListener('online', () => {
      if (navigator.serviceWorker.controller) {
        navigator.serviceWorker.controller.postMessage({ type: 'SYNC_NOW' });
      }
    });
  }
});
