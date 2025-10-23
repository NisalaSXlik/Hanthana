document.addEventListener('DOMContentLoaded', function() {
    // ===== HIGHLIGHT ACTIVE GROUP IN SIDEBAR =====
    const currentUrl = window.location.href;
    const urlParams = new URLSearchParams(window.location.search);
    const currentGroupId = urlParams.get('group_id');
    
    if (currentGroupId) {
        const groupItems = document.querySelectorAll('.group[data-group-id]');
        groupItems.forEach(item => {
            const groupId = item.getAttribute('data-group-id');
            if (groupId === currentGroupId) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    }

    // ===== SEE ALL GROUPS BUTTON =====
    const seeAllGroupsBtn = document.getElementById('seeAllGroupsBtn');
    if (seeAllGroupsBtn) {
        seeAllGroupsBtn.addEventListener('click', function() {
            window.location.href = 'allgroups.php';
        });
    }

    // ===== DROPDOWN MENU =====
    const optionsBtn = document.getElementById('groupOptionsBtn');
    const optionsMenu = document.getElementById('groupOptionsMenu');
    const editOption = document.getElementById('editGroupOption');
    const deleteOption = document.getElementById('deleteGroupOption');

    if (optionsBtn && optionsMenu) {
        optionsBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isVisible = optionsMenu.style.display === 'block';
            optionsMenu.style.display = isVisible ? 'none' : 'block';
        });

        document.addEventListener('click', () => {
            optionsMenu.style.display = 'none';
        });

        optionsMenu.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    }

    // ===== EDIT GROUP MODAL =====
    const editModal = document.getElementById('editGroupModal');
    const closeEditModal = document.getElementById('closeEditGroupModal');
    const cancelEditBtn = document.getElementById('cancelEditGroupBtn');

    const openEditModal = () => {
        if (editModal) {
            editModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            if (optionsMenu) optionsMenu.style.display = 'none';
        }
    };

    const closeEditModalFn = () => {
        if (editModal) {
            editModal.style.display = 'none';
            document.body.style.overflow = '';
        }
    };

    if (editOption) editOption.addEventListener('click', (e) => {
        e.preventDefault();
        openEditModal();
    });

    // Header quick actions: Edit Cover / Edit DP
    const headerEditCoverBtn = document.querySelector('.edit-cover-btn');
    const headerEditDpBtn = document.querySelector('.edit-dp-btn');

    // Per request: directly open the media selector without full form/modal
    if (headerEditCoverBtn) {
        headerEditCoverBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const quickInput = document.getElementById('editGroupCover') || document.getElementById('quickGroupCoverInput');
            if (quickInput) {
                quickInput.click();
            }
        });
    }

    if (headerEditDpBtn) {
        headerEditDpBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const quickInput = document.getElementById('editGroupDP') || document.getElementById('quickGroupDpInput');
            if (quickInput) {
                quickInput.click();
            }
        });
    }

    if (closeEditModal) closeEditModal.addEventListener('click', closeEditModalFn);
    if (cancelEditBtn) cancelEditBtn.addEventListener('click', closeEditModalFn);
    if (editModal) {
        editModal.addEventListener('click', (e) => {
            if (e.target === editModal) closeEditModalFn();
        });
    }

    // ===== DELETE GROUP MODAL =====
    const deleteModal = document.getElementById('deleteGroupModal');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

    const openDeleteModal = () => {
        if (deleteModal) {
            deleteModal.classList.add('active');
            document.body.style.overflow = 'hidden';
            if (optionsMenu) optionsMenu.style.display = 'none';
        }
    };

    const closeDeleteModal = () => {
        if (deleteModal) {
            deleteModal.classList.remove('active');
            document.body.style.overflow = '';
        }
    };

    if (deleteOption) deleteOption.addEventListener('click', (e) => {
        e.preventDefault();
        openDeleteModal();
    });

    if (cancelDeleteBtn) cancelDeleteBtn.addEventListener('click', closeDeleteModal);

    // Delete Group
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', async () => {
            const groupIdInput = document.querySelector('input[name="group_id"]');
            const groupId = groupIdInput ? groupIdInput.value : null;
            
            if (!groupId) {
                alert('Group ID not found');
                return;
            }
            
            try {
                const response = await fetch(BASE_PATH + 'index.php?controller=Group&action=handleAjax', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `sub_action=delete&group_id=${groupId}`
                });
                const result = await response.json();
                if (result.success) {
                    alert('Group deleted successfully');
                    window.location.href = BASE_PATH + 'views/myfeed.php';  // Ensure correct path
                } else {
                    alert(result.message || 'Failed to delete group');
                }
            } catch (err) {
                alert('An error occurred while deleting the group');
                console.error(err);
            }
            closeDeleteModal();
        });
    }

    // ===== IMAGE PREVIEW =====
    const coverInput = document.getElementById('editGroupCover');
    const coverPreview = document.getElementById('coverPreview');
    const dpInput = document.getElementById('editGroupDP');
    const dpPreview = document.getElementById('dpPreview');

    if (coverInput && coverPreview) {
        coverInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (evt) => {
                    coverPreview.src = evt.target.result;
                    coverPreview.style.display = 'block';
                    const headerImg = document.getElementById('groupCoverImage');
                    if (headerImg) headerImg.src = evt.target.result;
                };
                reader.readAsDataURL(file);
            } else {
                coverPreview.src = '';
                coverPreview.style.display = 'none';
            }
        });
    }

    if (dpInput && dpPreview) {
        dpInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (evt) => {
                    dpPreview.src = evt.target.result;
                    dpPreview.style.display = 'block';
                    const headerImg = document.getElementById('groupDpImage');
                    if (headerImg) headerImg.src = evt.target.result;
                };
                reader.readAsDataURL(file);
            } else {
                dpPreview.src = '';
                dpPreview.style.display = 'none';
            }
        });
    }

    // ===== EDIT GROUP FORM SUBMISSION =====
    const editGroupForm = document.getElementById('editGroupForm');
    // Edit Group Form
    if (editGroupForm) {
        editGroupForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('sub_action', 'edit');

            // Debug: log all form data
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }

            try {
                const response = await fetch(BASE_PATH + 'index.php?controller=Group&action=handleAjax', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    window.location.reload();
                } else {
                    alert(result.message || 'Failed to update group.');
                }
            } catch (err) {
                alert('An error occurred.');
                console.error(err);
            }
        });
    }

    // ===== TAB SWITCHING =====
    const tabLinks = document.querySelectorAll('.profile-tabs a');
    const tabContents = document.querySelectorAll('.tab-content');

    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const tabId = this.getAttribute('data-tab');

            // Remove active class from all tabs
            tabLinks.forEach(l => l.parentElement.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));

            // Add active class to clicked tab
            this.parentElement.classList.add('active');
            document.getElementById(tabId + '-content').classList.add('active');
        });
    });

    // ===== EVENT INTEREST BUTTONS =====
    const interestButtons = document.querySelectorAll('.event-interest-btn');
    interestButtons.forEach((button) => {
        const defaultText = button.dataset.defaultText || button.textContent.trim();
        const activeText = button.dataset.activeText || 'Interested';

        // Ensure initial text matches configured default
        button.textContent = defaultText;

        button.addEventListener('click', () => {
            const isActive = button.classList.toggle('interested');
            button.textContent = isActive ? activeText : defaultText;
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    });

    // ===== JOIN/INVITE BUTTONS =====
    const joinBtn = document.querySelector('.join-btn');
    const leaveBtn = document.querySelector('.leave-btn');
    const inviteBtn = document.querySelector('.invite-btn');

    if (joinBtn) {
        joinBtn.addEventListener('click', async () => {
            try {
                const response = await fetch(BASE_PATH + 'index.php?controller=Group&action=handleAjax', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `sub_action=join&group_id=${GROUP_ID}`
                });
                const result = await response.json();
                if (result.success) {
                    window.location.reload();
                } else {
                    alert(result.message || 'Failed to join group');
                }
            } catch (err) {
                alert('An error occurred');
                console.error(err);
            }
        });
    }

    if (leaveBtn) {
        leaveBtn.addEventListener('click', async () => {
            if (!confirm('Leave this group?')) return;
            try {
                const response = await fetch(BASE_PATH + 'index.php?controller=Group&action=handleAjax', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `sub_action=leave&group_id=${GROUP_ID}`
                });
                const result = await response.json();
                if (result.success) {
                    window.location.reload();
                } else {
                    alert(result.message || 'Failed to leave group');
                }
            } catch (err) {
                alert('An error occurred');
                console.error(err);
            }
        });
    }

    if (inviteBtn) {
        inviteBtn.addEventListener('click', () => {
            // Implement invite functionality
            alert('Invite functionality coming soon!');
        });
    }
});

// Create Group Modal Handler (if needed, but moved to general.js)
const addGroupBtn = document.querySelector('.btn-add-group');
if (addGroupBtn) {
    addGroupBtn.addEventListener('click', (e) => {
        e.preventDefault();  // Prevent navigation
        // Open modal logic if not in general.js
    });
}