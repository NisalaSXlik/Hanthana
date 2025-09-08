// general.js - Handles general functionality used across the site
document.addEventListener('DOMContentLoaded', function() {
    // Generate random likes and comments for existing posts
    document.querySelectorAll('.feed').forEach((feed, index) => {
        const likeCount = feed.querySelector('.liked-by p');
        const commentCount = feed.querySelector('.comments');
        
        if (likeCount) {
            const randomLikes = Math.floor(Math.random() * 100) + 50;
            likeCount.innerHTML = likeCount.innerHTML.replace(/\d+ others/, `${randomLikes} others`);
        }
        
        if (commentCount) {
            const randomComments = Math.floor(Math.random() * 30) + 10;
            commentCount.textContent = `View all ${randomComments} comments`;
        }
    });
    
    // Interactive like buttons
    document.querySelectorAll('.uil-heart').forEach(heart => {
        heart.addEventListener('click', function() {
            if (!isUserLoggedIn()) {
                showLoginModal();
                return;
            }
            
            this.classList.toggle('liked');
            if (this.classList.contains('liked')) {
                this.style.color = 'var(--color-danger)';
            } else {
                this.style.color = '';
            }
        });
    });
    
    // Toast notification system
    window.showToast = function(message, type = 'success') {
        let toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toastContainer';
            toastContainer.className = 'toast-container';
            document.body.appendChild(toastContainer);
        }

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        const icons = {
            success: 'uil-check-circle',
            error: 'uil-times-circle',
            info: 'uil-info-circle'
        };
        toast.innerHTML = `<i class="uil ${icons[type] || icons.success}"></i> ${message}`;
        
        toastContainer.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('fade-out');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    };
});

// Helper function to show login modal
function showLoginModal() {
    const signupModal = document.getElementById('signupModal');
    if (signupModal) {
        signupModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}