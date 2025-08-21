
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.mark-as-seen').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();

            const url = this.getAttribute('href');
            const notificationItem = this.closest('.notification-item');

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Retirer le bouton
                    this.remove();
                    // Optionnel: changer l'apparence de la notification
                    notificationItem.style.opacity = "0.5";
                }
            })
            .catch(err => console.error(err));
        });
    });
});