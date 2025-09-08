// notificationpopup.js - Handles notifications functionality
document.addEventListener('DOMContentLoaded', function() {
    // Auto-update notification count every minute
    const notificationCount = document.querySelector('.notification-count');
    if (notificationCount) {
        setInterval(() => {
            const currentCount = parseInt(notificationCount.textContent);
            if (currentCount < 15) {
                const newCount = currentCount + Math.floor(Math.random() * 3);
                notificationCount.textContent = newCount > 9 ? '9+' : newCount;
            }
        }, 60000);
    }
    
    // Notification popup interactions
    const notificationIcon = document.querySelector('.notification');
    if (notificationIcon) {
        notificationIcon.addEventListener('click', function(e) {
            if (!isUserLoggedIn()) {
                e.preventDefault();
                showLoginModal();
            }
        });
    }
});

