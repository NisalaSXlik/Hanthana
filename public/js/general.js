// general.js - Handles general functionality used across the site
document.addEventListener('DOMContentLoaded', function() {
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
        addGroupBtn.addEventListener('click', (e) => {
            e.preventDefault();
            createGroupModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });

        // Close modal handlers
        const closeModal = () => {
            createGroupModal.classList.remove('active');
            document.body.style.overflow = '';
            if (createGroupForm) {
                createGroupForm.reset();
            }
        };

        if (closeGroupModal) closeGroupModal.addEventListener('click', closeModal);
        if (cancelGroupBtn) cancelGroupBtn.addEventListener('click', closeModal);
        
        // Close on overlay click
        createGroupModal.addEventListener('click', (e) => {
            if (e.target === createGroupModal) closeModal();
        });

        // Handle form submission
        if (createGroupForm) {
            createGroupForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const errorMsgDiv = document.getElementById('groupErrorMsg');
            if (errorMsgDiv) {
                errorMsgDiv.style.display = 'none';
                errorMsgDiv.textContent = '';
            }
            const formData = new FormData(createGroupForm);
            const groupData = Object.fromEntries(formData.entries());
            try {
                const response = await fetch(BASE_PATH + 'index.php?controller=Group&action=handleAjax', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ sub_action: 'create', ...groupData })
                });
                const result = await response.json();
                if (result.success) {
                    if (errorMsgDiv) {
                        errorMsgDiv.style.display = 'none';
                        errorMsgDiv.textContent = '';
                    }
                    showToast('Group created successfully!', 'success');
                    closeModal();
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    if (errorMsgDiv) {
                        errorMsgDiv.textContent = result.message || 'Failed to create group';
                        errorMsgDiv.style.display = 'block';
                    } else {
                        showToast(result.message || 'Failed to create group', 'error');
                    }
                }
            } catch (error) {
                console.error('Error creating group:', error);
                if (errorMsgDiv) {
                    errorMsgDiv.textContent = 'An error occurred. Please try again.';
                    errorMsgDiv.style.display = 'block';
                } else {
                    showToast('An error occurred. Please try again.', 'error');
                }
                }
            });
        }
    }

    // All groups overview modal
    const seeAllGroupsBtn = document.getElementById('seeAllGroupsBtn');
    const allGroupsModal = document.getElementById('allGroupsModal');
    const closeAllGroupsBtn = document.getElementById('closeAllGroupsModal');

    if (seeAllGroupsBtn && allGroupsModal) {
        const toggleScroll = (lock) => {
            document.body.style.overflow = lock ? 'hidden' : '';
        };

        const openModal = () => {
            allGroupsModal.classList.add('active');
            toggleScroll(true);
        };

        const closeModal = () => {
            allGroupsModal.classList.remove('active');
            toggleScroll(false);
        };

        seeAllGroupsBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openModal();
        });

        if (closeAllGroupsBtn) {
            closeAllGroupsBtn.addEventListener('click', closeModal);
        }

        allGroupsModal.addEventListener('click', (e) => {
            if (e.target === allGroupsModal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && allGroupsModal.classList.contains('active')) {
                closeModal();
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

// Helper function to update sidebar group member count
window.updateSidebarGroupMemberCount = function(groupId, delta = 0) {
    const groupElement = document.querySelector(`.group[data-group-id="${groupId}"]`);
    if (!groupElement) return;
    
    const memberCountElement = groupElement.querySelector('.group-member-count');
    if (!memberCountElement) return;
    
    // Extract current count from text like "5 members"
    const currentText = memberCountElement.textContent.trim();
    const currentCount = parseInt(currentText.match(/\d+/)?.[0] || '0');
    
    // Calculate new count
    const newCount = Math.max(0, currentCount + delta);
    
    // Update display
    memberCountElement.textContent = `${newCount} member${newCount !== 1 ? 's' : ''}`;
};