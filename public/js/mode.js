
function toggleMode() {
    const body = document.body;
    const isDarkMode = body.classList.contains('dark-mode');
    const newMode = isDarkMode ? 'light' : 'dark';

    // Basculer entre les classes 'dark-mode' et 'light-mode'
    body.classList.toggle('dark-mode', newMode === 'dark');
    body.classList.toggle('light-mode', newMode === 'light');

    // Enregistrer la préférence du mode dans le localStorage
    localStorage.setItem('mode', newMode);
    console.log("Mode enregistré:", newMode);  // Log pour vérifier

    updateButtonLabel();
}

function updateButtonLabel() {
    const modeButton = document.getElementById('modeButton');
    modeButton.innerHTML = document.body.classList.contains('dark-mode')
        ? '<i class="bi bi-sun"></i>'
        : '<i class="bi bi-moon-fill"></i>';
}

document.addEventListener('DOMContentLoaded', () => {
    // Appliquer le mode stocké dans le localStorage
    const savedMode = localStorage.getItem('mode');
    console.log("Mode récupéré:", savedMode);  // Log pour vérifier
    if (savedMode) {
        document.body.classList.add(savedMode === 'dark' ? 'dark-mode' : 'light-mode');
    } else {
        // Si aucune préférence n'est enregistrée, appliquer le mode clair par défaut
        document.body.classList.add('light-mode');
    }
    updateButtonLabel();
});