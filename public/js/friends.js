// friends.js - Handles friend request functionality
document.addEventListener('DOMContentLoaded', function() {
    const friendRequestsContainer = document.querySelector('.friend-requests');
    if (!friendRequestsContainer) return;
    
    const acceptButtons = document.querySelectorAll('.accept-btn');
    const declineButtons = document.querySelectorAll('.decline-btn');
    
    acceptButtons.forEach(button => {
        button.addEventListener('click', function() {
            const request = this.closest('.request');
            const userName = request.querySelector('h5').textContent;
            
            request.remove();
            updateRequestCount();
            showToast(`You are now friends with ${userName}!`, 'success');
        });
    });
    
    declineButtons.forEach(button => {
        button.addEventListener('click', function() {
            const request = this.closest('.request');
            request.remove();
            updateRequestCount();
            showToast('Request declined', 'info');
        });
    });
    
    function updateRequestCount() {
        const badge = document.querySelector('.badge');
        const remainingRequests = document.querySelectorAll('.request').length;
        badge.textContent = `(${remainingRequests})`;
        
        if (remainingRequests === 0) {
            badge.style.display = 'none';
        }
    }
});