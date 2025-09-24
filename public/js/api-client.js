// Fonction utilitaire pour les appels API
async function apiCall(url, options = {}) {
    // Récupérer le token CSRF
    const csrfToken = document.querySelector('meta[name="api-csrf-token"]').getAttribute('content');
    
    // Configuration par défaut
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        credentials: 'include'
    };

    // Fusionner les options
    const finalOptions = {
        ...defaultOptions,
        ...options,
        headers: {
            ...defaultOptions.headers,
            ...(options.headers || {})
        }
    };

    try {
        const response = await fetch(url, finalOptions);
        
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'Erreur lors de la requête API');
        }

        return await response.json();
    } catch (error) {
        console.error('Erreur API:', error);
        throw error;
    }
}

// Exemple d'utilisation:
// Récupérer les familles
// apiCall('/api/families')
//     .then(data => console.log('Familles:', data))
//     .catch(error => console.error('Erreur:', error));
