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

    // Create Group Modal Handler
    const addGroupBtn = document.querySelector('.btn-add-group');
    const createGroupModal = document.getElementById('createGroupModal');
    const closeGroupModal = document.getElementById('closeGroupModal');
    const cancelGroupBtn = document.getElementById('cancelGroupBtn');
    const createGroupForm = document.getElementById('createGroupForm');

    if (addGroupBtn && createGroupModal) {
        // Open modal
        addGroupBtn.addEventListener('click', () => {
            createGroupModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });

        // Close modal handlers
        const closeModal = () => {
            createGroupModal.classList.remove('active');
            document.body.style.overflow = '';
            createGroupForm.reset();
        };

        if (closeGroupModal) closeGroupModal.addEventListener('click', closeModal);
        if (cancelGroupBtn) cancelGroupBtn.addEventListener('click', closeModal);
        
        // Close on overlay click
        createGroupModal.addEventListener('click', (e) => {
            if (e.target === createGroupModal) closeModal();
        });

        // Handle form submission
        createGroupForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(createGroupForm);
            const groupData = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('../../app/controllers/GroupController.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'create',
                        ...groupData
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showToast('Group created successfully!', 'success');
                    closeModal();
                    // Optional: Refresh the group list or redirect
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showToast(result.message || 'Failed to create group', 'error');
                }
            } catch (error) {
                console.error('Error creating group:', error);
                showToast('An error occurred. Please try again.', 'error');
            }
        });
    }
});

// Helper function to show login modal
function showLoginModal() {
    const signupModal = document.getElementById('signupModal');
    if (signupModal) {
        signupModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}