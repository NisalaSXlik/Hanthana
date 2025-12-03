document.addEventListener('DOMContentLoaded', function() {
    console.log('=== GROUP PROFILE VIEW JS LOADED ===');
    
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

    // ===== DROPDOWN MENU =====
    const optionsBtn = document.getElementById('groupOptionsBtn');
    const optionsMenu = document.getElementById('groupOptionsMenu');
    const editOption = document.getElementById('editGroupOption');
    const manageRequestsOption = document.getElementById('manageRequestsOption');
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

    // Handle Manage Requests option click
    if (manageRequestsOption) {
        manageRequestsOption.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            console.log('Manage Requests clicked');
            console.log('BASE_PATH:', BASE_PATH);
            console.log('GROUP_ID:', GROUP_ID);
            if (optionsMenu) optionsMenu.style.display = 'none';
            const url = BASE_PATH + 'index.php?controller=Group&action=manage&group_id=' + GROUP_ID;
            console.log('Navigating to:', url);
            window.location.href = url;
        });
    } else {
        console.log('manageRequestsOption not found');
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
                    window.showToast('Group deleted successfully', 'success');
                    window.location.href = BASE_PATH + 'index.php?controller=Feed&action=index';
                } else {
                    window.showToast(result.message || 'Failed to delete group', 'error');
                }
            } catch (err) {
                window.showToast('An error occurred while deleting the group', 'error');
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
        coverInput.addEventListener('change', async function(e) {
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
                
                // Auto-submit when cover is selected from quick edit button
                await uploadQuickImage('cover_image', file);
            } else {
                coverPreview.src = '';
                coverPreview.style.display = 'none';
            }
        });
    }

    if (dpInput && dpPreview) {
        dpInput.addEventListener('change', async function(e) {
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
                
                // Auto-submit when DP is selected from quick edit button
                await uploadQuickImage('display_picture', file);
            } else {
                dpPreview.src = '';
                dpPreview.style.display = 'none';
            }
        });
    }

    // Function to upload image immediately when selected
    async function uploadQuickImage(fieldName, file) {
        const groupIdInput = document.querySelector('input[name="group_id"]');
        const groupId = groupIdInput ? groupIdInput.value : null;
        
        if (!groupId) {
            alert('Group ID not found');
            return;
        }
        
        const formData = new FormData();
        formData.append('sub_action', 'edit');
        formData.append('group_id', groupId);
        formData.append(fieldName, file);
        
        try {
            const response = await fetch(BASE_PATH + 'index.php?controller=Group&action=handleAjax', {
                method: 'POST',
                body: formData
            });
            
            const text = await response.text();
            console.log('Upload response:', text);
            
            const data = JSON.parse(text);
            
            if (data.success) {
                showToast('Image updated successfully!', 'success');
            } else {
                showToast(data.message || 'Failed to update image', 'error');
            }
        } catch (error) {
            console.error('Error uploading image:', error);
            showToast('Error updating image', 'error');
        }
    }

    // Helper function to show toast messages
    function showToast(message, type = 'success') {
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
                    window.showToast(result.message || 'Failed to update group.', 'error');
                }
            } catch (err) {
                window.showToast('An error occurred.', 'error');
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
        const isInitiallyActive = button.classList.contains('interested');
        button.setAttribute('aria-pressed', isInitiallyActive ? 'true' : 'false');

        button.addEventListener('click', async () => {
            if (button.dataset.loading === '1') {
                return;
            }
            button.dataset.loading = '1';
            button.classList.add('loading');

            try {
                const response = await fetch(BASE_PATH + 'index.php?controller=Group&action=handleAjax', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        sub_action: 'toggleEventReminder',
                        group_id: button.dataset.groupId || GROUP_ID,
                        post_id: button.dataset.postId,
                        event_title: button.dataset.eventTitle,
                        event_date: button.dataset.eventDate,
                        event_time: button.dataset.eventTime,
                        event_location: button.dataset.eventLocation,
                        event_description: button.dataset.eventDescription
                    })
                });

                const result = await response.json();
                if (result.success) {
                    const interested = !!result.interested;
                    button.classList.toggle('interested', interested);
                    
                    const icon = button.querySelector('i');
                    if (icon) {
                        icon.className = interested ? 'uil uil-check' : 'uil uil-star';
                    }
                    
                    button.setAttribute('aria-pressed', interested ? 'true' : 'false');
                    const toastType = interested ? 'success' : 'info';
                    const toastMessage = result.message || (interested ? 'Event saved to your calendar' : 'Removed from your calendar');
                    if (typeof window.showToast === 'function') {
                        window.showToast(toastMessage, toastType);
                    }
                    document.dispatchEvent(new CustomEvent('calendar:refresh'));
                } else {
                    if (typeof window.showToast === 'function') {
                        window.showToast(result.message || 'Unable to update reminder', 'error');
                    }
                }
            } catch (err) {
                console.error('toggleEventReminder error:', err);
                if (typeof window.showToast === 'function') {
                    window.showToast('Something went wrong. Try again.', 'error');
                }
            } finally {
                button.dataset.loading = '0';
                button.classList.remove('loading');
            }
        });
    });

    // ===== JOIN/INVITE BUTTONS =====
    const joinBtn = document.querySelector('.join-btn');
    const leaveBtn = document.querySelector('.leave-btn');
    const inviteBtn = document.querySelector('.invite-btn');

    if (joinBtn) {
        const membershipFromDataset = (joinBtn.getAttribute('data-membership') || '').toLowerCase();
        const pendingFromDataset = joinBtn.getAttribute('data-pending') === '1' || membershipFromDataset === 'pending';
        const pendingFromServerFlag = typeof HAS_PENDING_REQUEST !== 'undefined' && HAS_PENDING_REQUEST === true;
        if (pendingFromDataset || pendingFromServerFlag) {
            joinBtn.textContent = 'Request sent';
            joinBtn.classList.add('request-sent');
            joinBtn.disabled = true;
            joinBtn.setAttribute('data-pending', '1');
        }

        joinBtn.addEventListener('click', async () => {
            // Prevent immediate duplicate clicks by disabling button until we get a response
            if (joinBtn.disabled) return;
            joinBtn.disabled = true;
            const previousText = joinBtn.textContent;
            joinBtn.textContent = 'Sending...';
            try {
                const response = await fetch(BASE_PATH + 'index.php?controller=Group&action=handleAjax', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `sub_action=join&group_id=${GROUP_ID}`
                });
                const result = await response.json();
                if (result.success) {
                    // If server indicates a join request was created (private group)
                    if (result.pending || (result.message && /request/i.test(result.message))) {
                        window.showToast(result.message || 'Request sent', 'success');
                        // update button UI to show request sent
                        joinBtn.textContent = 'Request sent';
                        joinBtn.classList.add('request-sent');
                        joinBtn.disabled = true;
                        joinBtn.setAttribute('data-pending', '1');
                        joinBtn.setAttribute('data-membership', (result.membership_state || 'pending'));
                    } else {
                        // Joined immediately (public group) â€” keep existing behavior
                        window.showToast(result.message || 'Joined group', 'success');
                        // Update sidebar member count before reload
                        if (window.updateSidebarGroupMemberCount) {
                            window.updateSidebarGroupMemberCount(GROUP_ID, 1);
                        }
                        window.location.reload();
                    }
                } else {
                    // Handle already-pending or errors
                    if (result.message && /pending|already have|already requested/i.test(result.message)) {
                        window.showToast(result.message, 'info');
                        joinBtn.textContent = 'Request sent';
                        joinBtn.classList.add('request-sent');
                        joinBtn.disabled = true;
                        joinBtn.setAttribute('data-pending', '1');
                        joinBtn.setAttribute('data-membership', (result.membership_state || 'pending'));
                    } else {
                        window.showToast(result.message || 'Failed to join group', 'error');
                        // restore button state on failure
                        joinBtn.disabled = false;
                        joinBtn.textContent = previousText;
                        joinBtn.setAttribute('data-pending', '0');
                        joinBtn.setAttribute('data-membership', 'none');
                    }
                }
            } catch (err) {
                window.showToast('An error occurred', 'error');
                console.error(err);
                joinBtn.disabled = false;
                joinBtn.textContent = previousText;
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
                    // Update sidebar member count before reload
                    if (window.updateSidebarGroupMemberCount) {
                        window.updateSidebarGroupMemberCount(GROUP_ID, -1);
                    }
                    window.location.reload();
                } else {
                    window.showToast(result.message || 'Failed to leave group', 'error');
                }
            } catch (err) {
                window.showToast('An error occurred', 'error');
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

    // Approve / Reject pending join requests (admin actions) - Use event delegation
    document.addEventListener('click', async (e) => {
        // Approve request handler
        if (e.target.classList.contains('approve-request') || e.target.closest('.approve-request')) {
            e.preventDefault();
            e.stopPropagation();
            
            const btn = e.target.classList.contains('approve-request') ? e.target : e.target.closest('.approve-request');
            const userId = btn.dataset.userId;
            const groupId = typeof GROUP_ID !== 'undefined' ? GROUP_ID : btn.dataset.groupId;
            
            console.log('Approve clicked - userId:', userId, 'groupId:', groupId);
            
            if (!groupId || !userId) {
                window.showToast('Missing required data', 'error');
                return;
            }
            
            if (!confirm('Approve this user to join the group?')) return;
            
            // Disable button during request
            btn.disabled = true;
            const originalText = btn.textContent;
            btn.textContent = 'Approving...';
            
            try {
                const resp = await fetch(BASE_PATH + 'index.php?controller=Group&action=handleAjax', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `sub_action=approve_request&group_id=${groupId}&user_id=${userId}`
                });
                const data = await resp.json();
                if (data.success) {
                    window.showToast(data.message || 'User approved and added to group', 'success');
                    // Update sidebar member count
                    if (window.updateSidebarGroupMemberCount) {
                        window.updateSidebarGroupMemberCount(groupId, 1);
                    }
                    // remove request item
                    const item = btn.closest('.request-row') || btn.closest('.request-item');
                    if (item) {
                        item.style.opacity = '0';
                        item.style.transform = 'translateX(20px)';
                        setTimeout(() => item.remove(), 300);
                    }
                    
                    // Check if no more requests remain after a short delay
                    setTimeout(() => {
                        const remaining = document.querySelectorAll('.request-row, .request-item');
                        if (!remaining || remaining.length === 0) {
                            const manageContent = document.querySelector('.manage-content');
                            const requestsList = document.querySelector('.requests-list');
                            if (manageContent && requestsList) {
                                manageContent.innerHTML = `
                                    <div class="empty-state manage-empty">
                                        <div class="empty-illustration">
                                            <i class="uil uil-inbox" style="font-size:48px;color:var(--color-gray);"></i>
                                        </div>
                                        <h3>No pending join requests</h3>
                                        <p class="muted">There are currently no users waiting to join this group. When someone requests to join, you'll see their request here with options to approve or reject.</p>
                                        <div class="empty-actions">
                                            <a href="${BASE_PATH}index.php?controller=Group&action=index&group_id=${GROUP_ID}" class="btn btn-secondary">View Group</a>
                                            <button id="refreshRequestsBtn" class="btn btn-primary">Refresh</button>
                                        </div>
                                    </div>
                                `;
                                const refreshBtn = document.getElementById('refreshRequestsBtn');
                                if (refreshBtn) refreshBtn.addEventListener('click', () => window.location.reload());
                            }
                        }
                    }, 350);
                } else {
                    window.showToast(data.message || 'Failed to approve request', 'error');
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            } catch (err) {
                console.error(err);
                window.showToast('An error occurred while approving', 'error');
                btn.disabled = false;
                btn.textContent = originalText;
            }
        }
        
        // Reject request handler
        if (e.target.classList.contains('reject-request') || e.target.closest('.reject-request')) {
            e.preventDefault();
            e.stopPropagation();
            
            const btn = e.target.classList.contains('reject-request') ? e.target : e.target.closest('.reject-request');
            const userId = btn.dataset.userId;
            const groupId = typeof GROUP_ID !== 'undefined' ? GROUP_ID : btn.dataset.groupId;
            
            console.log('Reject clicked - userId:', userId, 'groupId:', groupId);
            
            if (!groupId || !userId) {
                window.showToast('Missing required data', 'error');
                return;
            }
            
            if (!confirm('Reject this join request?')) return;
            
            btn.disabled = true;
            const originalText = btn.textContent;
            btn.textContent = 'Rejecting...';
            
            try {
                const resp = await fetch(BASE_PATH + 'index.php?controller=Group&action=handleAjax', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `sub_action=reject_request&group_id=${groupId}&user_id=${userId}`
                });
                const data = await resp.json();
                if (data.success) {
                    window.showToast(data.message || 'Request rejected', 'success');
                    const item = btn.closest('.request-row') || btn.closest('.request-item');
                    if (item) {
                        item.style.opacity = '0';
                        item.style.transform = 'translateX(-20px)';
                        setTimeout(() => item.remove(), 300);
                    }
                    
                    setTimeout(() => {
                        const remaining = document.querySelectorAll('.request-row, .request-item');
                        if (!remaining || remaining.length === 0) {
                            const manageContent = document.querySelector('.manage-content');
                            const requestsList = document.querySelector('.requests-list');
                            if (manageContent && requestsList) {
                                manageContent.innerHTML = `
                                    <div class="empty-state manage-empty">
                                        <div class="empty-illustration">
                                            <i class="uil uil-inbox" style="font-size:48px;color:var(--color-gray);"></i>
                                        </div>
                                        <h3>No pending join requests</h3>
                                        <p class="muted">There are currently no users waiting to join this group. When someone requests to join, you'll see their request here with options to approve or reject.</p>
                                        <div class="empty-actions">
                                            <a href="${BASE_PATH}index.php?controller=Group&action=index&group_id=${GROUP_ID}" class="btn btn-secondary">View Group</a>
                                            <button id="refreshRequestsBtn" class="btn btn-primary">Refresh</button>
                                        </div>
                                    </div>
                                `;
                                const refreshBtn = document.getElementById('refreshRequestsBtn');
                                if (refreshBtn) refreshBtn.addEventListener('click', () => window.location.reload());
                            }
                        }
                    }, 350);
                } else {
                    window.showToast(data.message || 'Failed to reject request', 'error');
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            } catch (err) {
                console.error(err);
                window.showToast('An error occurred while rejecting', 'error');
                btn.disabled = false;
                btn.textContent = originalText;
            }
    }
    });
// Create Group Modal Handler (moved to general.js)

    // Helper: attach approve/reject handlers to buttons within a root element
    function attachApproveRejectHandlers(root = document) {
        root.querySelectorAll('.approve-request').forEach(btn => {
            if (btn._attached) return; btn._attached = true;
            btn.addEventListener('click', async (e) => {
                const userId = btn.dataset.userId;
                const groupId = GROUP_ID;
                if (!confirm('Approve this user to join the group?')) return;
                try {
                    const resp = await fetch(BASE_PATH + 'index.php?controller=Group&action=handleAjax', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `sub_action=approve_request&group_id=${groupId}&user_id=${userId}`
                    });
                    const data = await resp.json();
                    if (data.success) {
                        window.showToast(data.message || 'Approved', 'success');
                        // Update sidebar member count
                        if (window.updateSidebarGroupMemberCount) {
                            window.updateSidebarGroupMemberCount(groupId, 1);
                        }
                        const item = btn.closest('.request-row') || btn.closest('.request-item'); if (item) item.remove();
                        const remaining = document.querySelectorAll('.request-row');
                        if (!remaining || remaining.length === 0) fetchPendingRequestsAndRender();
                    } else {
                        window.showToast(data.message || 'Failed', 'error');
                    }
                } catch (err) {
                    console.error(err);
                    window.showToast('An error occurred', 'error');
                }
            });
        });

        root.querySelectorAll('.reject-request').forEach(btn => {
            if (btn._attached) return; btn._attached = true;
            btn.addEventListener('click', async (e) => {
                const userId = btn.dataset.userId;
                const groupId = GROUP_ID;
                if (!confirm('Reject this join request?')) return;
                try {
                    const resp = await fetch(BASE_PATH + 'index.php?controller=Group&action=handleAjax', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `sub_action=reject_request&group_id=${groupId}&user_id=${userId}`
                    });
                    const data = await resp.json();
                    if (data.success) {
                        window.showToast(data.message || 'Rejected', 'success');
                        const item = btn.closest('.request-row') || btn.closest('.request-item'); if (item) item.remove();
                        const remaining = document.querySelectorAll('.request-row');
                        if (!remaining || remaining.length === 0) fetchPendingRequestsAndRender();
                    } else {
                        window.showToast(data.message || 'Failed', 'error');
                    }
                } catch (err) {
                    console.error(err);
                    window.showToast('An error occurred', 'error');
                }
            });
        });
    }

    // Fetch pending requests and render into the manage page
    async function fetchPendingRequestsAndRender() {
        try {
            const resp = await fetch(BASE_PATH + 'index.php?controller=Group&action=handleAjax', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `sub_action=fetch_pending_requests&group_id=${GROUP_ID}`
            });
            const data = await resp.json();
            if (!data.success) {
                console.warn('fetchPendingRequests failed', data);
                return;
            }
            const requests = data.requests || [];
            const manageContent = document.querySelector('.manage-content');
            if (!manageContent) return;
            if (requests.length === 0) {
                manageContent.innerHTML = `
                    <div class="empty-state manage-empty">
                        <div class="empty-illustration">
                            <i class="uil uil-inbox" style="font-size:48px;color:var(--color-gray);"></i>
                        </div>
                        <h3>No pending join requests</h3>
                        <p class="muted">There are currently no users waiting to join this group. When someone requests to join, you'll see their request here with options to approve or reject.</p>
                        <div class="empty-actions">
                            <a href="${BASE_PATH}index.php?controller=Group&action=index&group_id=${GROUP_ID}" class="btn btn-secondary">View Group</a>
                            <button id="refreshRequestsBtn" class="btn btn-primary">Refresh</button>
                        </div>
                    </div>
                `;
                const refreshBtn = document.getElementById('refreshRequestsBtn');
                if (refreshBtn) refreshBtn.addEventListener('click', () => fetchPendingRequestsAndRender());
                return;
            }

            let html = '<div class="requests-list">';
            requests.forEach(r => {
                let pic = '';
                if (r.profile_picture) {
                    pic = r.profile_picture.startsWith('http') ? r.profile_picture : (BASE_PATH + r.profile_picture);
                }
                html += `
                    <div class="request-row" id="request-${r.user_id}">
                        <div class="request-left">
                            <img class="request-dp" src="${pic}" alt="">
                            <div class="request-meta">
                                <strong>${escapeHtml(r.first_name + ' ' + r.last_name)}</strong>
                                <div class="muted">@${escapeHtml(r.username)} Â· ${escapeHtml(new Date(r.requested_at).toLocaleString())}</div>
                            </div>
                        </div>
                        <div class="request-actions">
                            <button class="btn btn-primary approve-request" data-user-id="${r.user_id}">Approve</button>
                            <button class="btn btn-secondary reject-request" data-user-id="${r.user_id}">Reject</button>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            manageContent.innerHTML = html;
            attachApproveRejectHandlers(manageContent);
        } catch (err) {
            console.error('Error fetching pending requests', err);
        }
    }

    function escapeHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

    // Initialize handlers for currently-rendered page and wire refresh button
    attachApproveRejectHandlers(document);
    const refreshBtnGlobal = document.getElementById('refreshRequestsBtn');
    if (refreshBtnGlobal) refreshBtnGlobal.addEventListener('click', () => fetchPendingRequestsAndRender());

    // ===== CREATE POST MODAL FUNCTIONALITY =====
    const createPostModal = document.getElementById('createPostModal');
    const quickPostTrigger = document.getElementById('quickPostTrigger');
    const photoQuickBtn = document.getElementById('photoQuickBtn');
    const videoQuickBtn = document.getElementById('videoQuickBtn');
    const eventQuickBtn = document.getElementById('eventQuickBtn');
    const openCreatePostBtn = document.getElementById('openCreatePostBtn');
    const closeCreatePostModal = document.getElementById('closeCreatePostModal');
    const cancelCreatePostBtn = document.getElementById('cancelCreatePostBtn');
    const createGroupPostForm = document.getElementById('createGroupPostForm');
    const postTypeBtns = document.querySelectorAll('.post-type-btn');
    const selectedPostType = document.getElementById('selectedPostType');
    const postImageInput = document.getElementById('postImageInput');
    const imagePreview = document.getElementById('imagePreview');
    const imagePreviewContainer = document.getElementById('imagePreviewContainer');
    const removeImageBtn = document.getElementById('removeImageBtn');
    const uploadImageBtn = document.getElementById('uploadImageBtn');
    const postFileInput = document.getElementById('postFileInput');
    const filePreviewContainer = document.getElementById('filePreviewContainer');
    const fileName = document.getElementById('fileName');
    const removeFileBtn = document.getElementById('removeFileBtn');
    const uploadFileBtn = document.getElementById('uploadFileBtn');
    const fileUploadSection = document.getElementById('fileUploadSection');

    // Open modal with specific post type
    function openCreatePostModal(type = 'discussion') {
        if (createPostModal) {
            createPostModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            if (type) {
                postTypeBtns.forEach(btn => btn.classList.remove('active'));
                const typeBtn = document.querySelector(`.post-type-btn[data-type="${type}"]`);
                if (typeBtn) {
                    typeBtn.classList.add('active');
                    selectedPostType.value = type;
                    showConditionalFields(type);
                }
            }
        }
    }

    // Close modal
    function closeCreatePostModalFn() {
        if (createPostModal) {
            createPostModal.style.display = 'none';
            document.body.style.overflow = '';
            resetCreatePostForm();
        }
    }

    // Reset form
    function resetCreatePostForm() {
        if (createGroupPostForm) {
            createGroupPostForm.reset();
            selectedPostType.value = 'discussion';
            postTypeBtns.forEach(btn => btn.classList.remove('active'));
            postTypeBtns[0].classList.add('active');
            document.querySelectorAll('.conditional-fields').forEach(field => {
                field.style.display = 'none';
            });
            if (imagePreviewContainer) imagePreviewContainer.style.display = 'none';
            if (filePreviewContainer) filePreviewContainer.style.display = 'none';
            if (fileUploadSection) fileUploadSection.style.display = 'none';
        }
    }

    // Show conditional fields based on post type
    function showConditionalFields(type) {
        document.querySelectorAll('.conditional-fields').forEach(field => {
            field.style.display = 'none';
        });
        
        const fieldMap = {
            'question': 'questionFields',
            'resource': 'resourceFields',
            'poll': 'pollFields',
            'event': 'eventFields',
            'assignment': 'assignmentFields'
        };
        
        const fieldId = fieldMap[type];
        if (fieldId) {
            const field = document.getElementById(fieldId);
            if (field) field.style.display = 'block';
        }

        if (fileUploadSection) {
            fileUploadSection.style.display = type === 'resource' ? 'block' : 'none';
        }
    }

    // Event listeners for opening modal
    if (quickPostTrigger) {
        quickPostTrigger.addEventListener('click', () => openCreatePostModal('discussion'));
    }

    if (openCreatePostBtn) {
        openCreatePostBtn.addEventListener('click', () => openCreatePostModal('discussion'));
    }

    if (photoQuickBtn) {
        photoQuickBtn.addEventListener('click', () => {
            openCreatePostModal('discussion');
            setTimeout(() => {
                if (uploadImageBtn) uploadImageBtn.click();
            }, 100);
        });
    }

    if (videoQuickBtn) {
        videoQuickBtn.addEventListener('click', () => {
            openCreatePostModal('discussion');
            if (typeof showToast === 'function') {
                showToast('Video upload coming soon!', 'info');
            }
        });
    }

    if (eventQuickBtn) {
        eventQuickBtn.addEventListener('click', () => openCreatePostModal('event'));
    }

    // Event listeners for closing modal
    if (closeCreatePostModal) {
        closeCreatePostModal.addEventListener('click', closeCreatePostModalFn);
    }

    if (cancelCreatePostBtn) {
        cancelCreatePostBtn.addEventListener('click', closeCreatePostModalFn);
    }

    // Post type selection
    postTypeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            postTypeBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const type = this.getAttribute('data-type');
            if (selectedPostType) selectedPostType.value = type;
            showConditionalFields(type);
        });
    });

    // Image upload
    if (uploadImageBtn) {
        uploadImageBtn.addEventListener('click', () => {
            if (postImageInput) postImageInput.click();
        });
    }

    if (postImageInput) {
        postImageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (imagePreview) imagePreview.src = e.target.result;
                    if (imagePreviewContainer) imagePreviewContainer.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }

    if (removeImageBtn) {
        removeImageBtn.addEventListener('click', function() {
            if (postImageInput) postImageInput.value = '';
            if (imagePreview) imagePreview.src = '';
            if (imagePreviewContainer) imagePreviewContainer.style.display = 'none';
        });
    }

    // File upload (for resources)
    if (uploadFileBtn) {
        uploadFileBtn.addEventListener('click', () => {
            if (postFileInput) postFileInput.click();
        });
    }

    if (postFileInput) {
        postFileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (fileName) fileName.textContent = file.name;
                if (filePreviewContainer) filePreviewContainer.style.display = 'block';
            }
        });
    }

    if (removeFileBtn) {
        removeFileBtn.addEventListener('click', function() {
            if (postFileInput) postFileInput.value = '';
            if (fileName) fileName.textContent = '';
            if (filePreviewContainer) filePreviewContainer.style.display = 'none';
        });
    }

    // Form submission
    if (createGroupPostForm) {
        createGroupPostForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submitPostBtn');
            if (!submitBtn) return;

            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Posting...';
            submitBtn.disabled = true;

            try {
                const formData = new FormData(createGroupPostForm);

                const response = await fetch(BASE_PATH + 'index.php?controller=Group&action=createPost', {
                    method: 'POST',
                    body: formData
                });

                const text = await response.text();
                console.log('Create post response:', text);
                
                const result = JSON.parse(text);

                if (result.success) {
                    if (typeof showToast === 'function') {
                        showToast('Post created successfully!', 'success');
                    }
                    closeCreatePostModalFn();
                    
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    if (typeof showToast === 'function') {
                        showToast(result.message || 'Failed to create post', 'error');
                    }
                }
            } catch (error) {
                console.error('Create post error:', error);
                if (typeof showToast === 'function') {
                    showToast('Failed to create post', 'error');
                }
            } finally {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }
        });
    }

    // ===== GROUP POLL VOTING =====
    const handlePollVote = async (optionBtn) => {
        const postId = optionBtn.getAttribute('data-post-id');
        const optionIndex = optionBtn.getAttribute('data-option-index');
        if (!postId || optionIndex === null) return;

        const pollCard = optionBtn.closest('.poll-card');
        if (!pollCard) return;

        const optionButtons = pollCard.querySelectorAll('.poll-option');
        optionButtons.forEach(btn => { btn.disabled = true; btn.classList.add('poll-option-loading'); });

        try {
            const body = new URLSearchParams({
                sub_action: 'votePollOption',
                post_id: postId,
                option_index: optionIndex
            }).toString();

            const response = await fetch(BASE_PATH + 'index.php?controller=Group&action=handleAjax', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body
            });

            const result = await response.json();
            if (!result.success) {
                if (typeof showToast === 'function') {
                    showToast(result.message || 'Unable to record vote', 'error');
                }
                return;
            }

            updatePollUi(pollCard, result);
        } catch (error) {
            console.error('Poll vote error:', error);
            if (typeof showToast === 'function') {
                showToast('Unable to record vote', 'error');
            }
        } finally {
            optionButtons.forEach(btn => {
                btn.disabled = false;
                btn.classList.remove('poll-option-loading');
            });
        }
    };

    const updatePollUi = (pollCard, voteResult) => {
        if (!pollCard || !Array.isArray(voteResult.votes)) return;

        const optionButtons = pollCard.querySelectorAll('.poll-option');
        const totalVotes = voteResult.votes.reduce((sum, val) => sum + Number(val || 0), 0);
        optionButtons.forEach((btn, idx) => {
            const voteCount = Number(voteResult.votes[idx] || 0);
            const percentage = totalVotes > 0 ? Math.round((voteCount / totalVotes) * 100) : 0;
            const percentageEl = btn.querySelector('.option-percentage');
            const votesEl = btn.querySelector('.option-votes');
            const progressFill = btn.querySelector('.progress-fill');
            if (percentageEl) percentageEl.textContent = `${percentage}%`;
            if (votesEl) votesEl.textContent = `${voteCount} vote${voteCount === 1 ? '' : 's'}`;
            if (progressFill) progressFill.style.width = `${percentage}%`;
            btn.classList.toggle('selected', typeof voteResult.selected === 'number' && voteResult.selected === idx);
        });

        const totalVotesEl = pollCard.querySelector('.total-votes');
        if (totalVotesEl) {
            totalVotesEl.textContent = `${totalVotes} total vote${totalVotes === 1 ? '' : 's'}`;
        }
    };

    const postsFeedContainer = document.querySelector('.posts-feed');
    if (postsFeedContainer) {
        postsFeedContainer.addEventListener('click', async (event) => {
            const optionBtn = event.target.closest('.poll-option');
            if (!optionBtn || optionBtn.disabled) return;
            event.preventDefault();
            await handlePollVote(optionBtn);
        });
    }

    // ===== INSTAGRAM-STYLE POST MODAL =====
    let currentPostIndex = 0;
    let currentPosts = window.GROUP_POSTS || [];
    const postViewModal = document.getElementById('postViewModal');
    const closeBtn = document.getElementById('closePostModal');
    const prevBtn = document.getElementById('prevPost');
    const nextBtn = document.getElementById('nextPost');
    const postViewImage = document.getElementById('postViewImage');
    const postViewImageContainer = postViewModal?.querySelector('.post-view-image');
    const postViewTextOnly = document.getElementById('postViewTextOnly');
    const postViewTextContent = document.getElementById('postViewTextContent');
    const postViewAvatar = document.getElementById('postViewUserAvatar');
    const postViewUsername = document.getElementById('postViewUsername');
    const postViewTimestamp = document.getElementById('postViewTimestamp');
    const postViewCaption = document.getElementById('postViewCaption');
    const postViewUpvoteBtn = document.getElementById('postViewUpvoteBtn');
    const postViewDownvoteBtn = document.getElementById('postViewDownvoteBtn');
    const postViewCommentToggle = document.getElementById('postViewCommentToggle');
    const postViewUpvoteCount = document.getElementById('postViewUpvoteCount');
    const postViewDownvoteCount = document.getElementById('postViewDownvoteCount');
    const postViewCommentCount = document.getElementById('postViewCommentCount');
    const postViewCommentBadge = document.getElementById('postViewCommentBadge');
    const postViewCommentsPanel = document.getElementById('postViewCommentsPanel');
    const postViewCommentsList = document.getElementById('postViewCommentsList');
    const postViewCommentForm = document.getElementById('postViewCommentForm');
    const postViewCommentInput = document.getElementById('postViewCommentInput');
    const postViewCommentSubmit = document.getElementById('postViewCommentSubmit');
    const postViewOverlay = postViewModal?.querySelector('.post-view-overlay');

    console.log('Group Profile Modal Elements:', {
        postViewModal: !!postViewModal,
        postViewUpvoteBtn: !!postViewUpvoteBtn,
        postViewDownvoteBtn: !!postViewDownvoteBtn,
        postViewCommentToggle: !!postViewCommentToggle
    });

    document.querySelectorAll('.group-post-clickable').forEach(postCard => {
        postCard.addEventListener('click', function(e) {
            if (e.target.closest('.action-buttons, .comment-section, .poll-option, button, a')) {
                return;
            }
            const contentArea = e.target.closest('.post-body');
            if (!contentArea || !postCard.contains(contentArea)) {
                return;
            }
            const index = parseInt(this.dataset.postIndex, 10);
            openPostModal(Number.isFinite(index) ? index : 0);
        });
    });

    function openPostModal(index) {
        currentPosts = window.GROUP_POSTS || [];
        if (!currentPosts.length) {
            console.warn('No group posts available');
            return;
        }

        currentPostIndex = Math.max(0, Math.min(index, currentPosts.length - 1));
        displayPost(currentPostIndex);
        postViewModal?.classList.add('active');
        document.body.style.overflow = 'hidden';
        updateNavigationButtons();
    }

    function closePostModal() {
        clearReplyForm();
        postViewModal?.classList.remove('active');
        document.body.style.overflow = '';
    }

    function displayPost(index) {
        const post = currentPosts[index];
        if (!post) return;

        console.log('displayPost called:', { index, post, postId: post.post_id || post.postId });

        if (post.image_url) {
            postViewImage.src = post.image_url;
            postViewImage.style.display = 'block';
            postViewTextOnly.style.display = 'none';
            postViewTextOnly.classList.remove('active');
            postViewImageContainer?.classList.remove('text-mode');
        } else {
            postViewImage.style.display = 'none';
            postViewTextOnly.style.display = 'flex';
            postViewTextOnly.classList.add('active');
            postViewImageContainer?.classList.add('text-mode');
            if (postViewTextContent) {
                postViewTextContent.textContent = post.content || 'No content available';
            }
        }

        const defaultAvatar = BASE_PATH + 'uploads/user_dp/default_user_dp.jpg';
        postViewAvatar.src = post.profile_picture || defaultAvatar;
        postViewUsername.textContent = `${post.first_name || ''} ${post.last_name || ''}`.trim() || 'Unknown User';
        postViewTimestamp.textContent = post.created_at || '';
        postViewCaption.textContent = post.content || '';

        if (postViewUpvoteCount) postViewUpvoteCount.textContent = post.upvote_count ?? '0';
        if (postViewDownvoteCount) postViewDownvoteCount.textContent = post.downvote_count ?? '0';
        if (postViewCommentCount) postViewCommentCount.textContent = post.comment_count ?? '0';
        if (postViewCommentBadge) postViewCommentBadge.textContent = post.comment_count ?? '0';

        updateModalVoteState(post.user_vote ? post.user_vote : null, 'init');
        if (postViewModal) postViewModal.dataset.postId = post.post_id || post.postId || '';

        if (postViewCommentsPanel) postViewCommentsPanel.classList.add('active');
        loadModalComments(post.post_id || post.postId);
    }

    function updateNavigationButtons() {
        if (!currentPosts.length) return;
        if (prevBtn) prevBtn.style.display = currentPostIndex > 0 ? 'flex' : 'none';
        if (nextBtn) nextBtn.style.display = currentPostIndex < currentPosts.length - 1 ? 'flex' : 'none';
    }

    function showPreviousPost() {
        if (currentPostIndex > 0) {
            currentPostIndex--;
            displayPost(currentPostIndex);
            updateNavigationButtons();
        }
    }

    function showNextPost() {
        if (currentPostIndex < currentPosts.length - 1) {
            currentPostIndex++;
            displayPost(currentPostIndex);
            updateNavigationButtons();
        }
    }

    function updateModalVoteState(voteType, action = '') {
        if (!postViewUpvoteBtn || !postViewDownvoteBtn) return;
        postViewUpvoteBtn.classList.remove('liked');
        postViewDownvoteBtn.classList.remove('liked');

        if (!voteType || action === 'removed') return;

        if (voteType === 'upvote') {
            postViewUpvoteBtn.classList.add('liked');
        } else if (voteType === 'downvote') {
            postViewDownvoteBtn.classList.add('liked');
        }
    }

    async function handleModalVote(voteType) {
        const postId = postViewModal?.dataset.postId;
        console.log('handleModalVote called:', { voteType, postId, modal: postViewModal });
        if (!postId) {
            console.warn('No postId found on modal');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('sub_action', 'vote');
            formData.append('post_id', postId);
            formData.append('vote_type', voteType);

            const response = await fetch(BASE_PATH + 'index.php?controller=Vote&action=handleAjax', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (!data.success) {
                window.showToast?.(data.message || 'Unable to submit vote', 'error');
                return;
            }

            updateModalVoteState(voteType, data.action);
            if (data.upvote_count !== undefined && postViewUpvoteCount) {
                postViewUpvoteCount.textContent = data.upvote_count;
            }
            if (data.downvote_count !== undefined && postViewDownvoteCount) {
                postViewDownvoteCount.textContent = data.downvote_count;
            }

            if (currentPosts[currentPostIndex]) {
                currentPosts[currentPostIndex].upvote_count = data.upvote_count ?? currentPosts[currentPostIndex].upvote_count;
                currentPosts[currentPostIndex].downvote_count = data.downvote_count ?? currentPosts[currentPostIndex].downvote_count;
                currentPosts[currentPostIndex].user_vote = data.user_vote ?? voteType;
            }
        } catch (error) {
            console.error('Modal vote error:', error);
            window.showToast?.('Unable to submit vote', 'error');
        }
    }

    async function loadModalComments(postId) {
        if (!postId || !postViewCommentsList) return;
        postViewCommentsList.innerHTML = '<div class="comments-loading">Loading comments...</div>';

        try {
            const formData = new FormData();
            formData.append('sub_action', 'load');
            formData.append('post_id', postId);
            const response = await fetch(BASE_PATH + 'index.php?controller=Comment&action=handleAjax', {
                method: 'POST',
                body: formData
            });
            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (parseError) {
                console.error('Modal comments parse error:', parseError, text);
                postViewCommentsList.innerHTML = '<div class="comments-loading">Unable to load comments</div>';
                return;
            }

            if (data.success) {
                postViewCommentsList.innerHTML = renderModalComments(data.comments || []);
                updateCommentBadges(data.comment_count ?? (currentPosts[currentPostIndex]?.comment_count || 0));
            } else {
                postViewCommentsList.innerHTML = `<div class="comments-loading">${data.message || 'No comments found'}</div>`;
            }
        } catch (error) {
            console.error('Modal comments error:', error);
            postViewCommentsList.innerHTML = '<div class="comments-loading">Error loading comments</div>';
        }
    }

    function updateCommentBadges(count) {
        const formatted = typeof count === 'number' ? count : (count ? Number(count) : 0);
        if (postViewCommentCount) postViewCommentCount.textContent = formatted;
        if (postViewCommentBadge) postViewCommentBadge.textContent = formatted;
        if (currentPosts[currentPostIndex]) {
            currentPosts[currentPostIndex].comment_count = formatted;
        }
    }

    function renderModalComments(comments) {
        if (!comments || comments.length === 0) {
            return '<div class="no-comments">No comments yet. Be the first to comment.</div>';
        }

        return comments.map(comment => {
            const author = escapeHtml(comment.username || comment.author || 'Unknown');
            const body = escapeHtml(comment.content || comment.comment || '');
            const timestamp = escapeHtml(comment.created_at || '');
            const repliesHtml = (comment.replies || []).map(renderModalReply).join('');
            const commentId = comment.comment_id ?? comment.commentId ?? '';
            return `
                <div class="modal-comment" data-comment-id="${commentId}">
                    <div class="comment-header">
                        <span>${author}</span>
                        <span>${timestamp}</span>
                    </div>
                    <div class="comment-body">${body}</div>
                    <button type="button" class="comment-reply-btn" data-parent-id="${commentId}">Reply</button>
                    <div class="reply-list">${repliesHtml}</div>
                </div>
            `;
        }).join('');
    }

    function renderModalReply(reply) {
        const author = escapeHtml(reply.username || reply.author || 'Unknown');
        const body = escapeHtml(reply.content || reply.comment || '');
        const timestamp = escapeHtml(reply.created_at || '');
        return `
            <div class="modal-reply">
                <div class="reply-header">
                    <span>${author}</span>
                    <span>${timestamp}</span>
                </div>
                <div class="reply-body">${body}</div>
            </div>
        `;
    }

    function escapeHtml(string) {
        if (typeof string !== 'string') return '';
        return string
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    let activeReplyForm = null;
    function clearReplyForm() {
        if (activeReplyForm) {
            activeReplyForm.remove();
            activeReplyForm = null;
        }
    }

    async function postCommentRequest(content, parentId = null) {
        const postId = postViewModal?.dataset.postId;
        if (!postId) return null;

        const formData = new FormData();
        formData.append('sub_action', 'add');
        formData.append('post_id', postId);
        formData.append('content', content);
        if (parentId) {
            formData.append('parent_comment_id', parentId);
        }

        try {
            const response = await fetch(BASE_PATH + 'index.php?controller=Comment&action=handleAjax', {
                method: 'POST',
                body: formData
            });
            const text = await response.text();
            const data = JSON.parse(text);
            if (!data.success) {
                window.showToast?.(data.message || 'Unable to post comment', 'error');
                return null;
            }
            return data;
        } catch (error) {
            console.error('Modal comment request error:', error);
            window.showToast?.('Unable to post comment', 'error');
            return null;
        }
    }

    async function submitModalComment(event) {
        if (event) event.preventDefault();
        if (!postViewCommentInput || !postViewCommentSubmit) return;

        const content = postViewCommentInput.value.trim();
        if (!content) return;

        postViewCommentSubmit.disabled = true;
        postViewCommentSubmit.textContent = 'Posting...';

        try {
            const data = await postCommentRequest(content);
            if (!data) return;
            postViewCommentInput.value = '';
            updateCommentBadges(data.comment_count ?? (currentPosts[currentPostIndex]?.comment_count || 0));
            await loadModalComments(postViewModal?.dataset.postId);
        } finally {
            postViewCommentSubmit.disabled = false;
            postViewCommentSubmit.textContent = 'Post';
        }
    }

    async function submitReply(targetForm, parentId) {
        const textarea = targetForm.querySelector('textarea');
        const submitButton = targetForm.querySelector('button');
        if (!textarea || !submitButton) return;
        const content = textarea.value.trim();
        if (!content) return;
        submitButton.disabled = true;
        submitButton.textContent = 'Replying...';

        const data = await postCommentRequest(content, parentId);
        submitButton.disabled = false;
        submitButton.textContent = 'Reply';

        if (data) {
            clearReplyForm();
            updateCommentBadges(data.comment_count ?? (currentPosts[currentPostIndex]?.comment_count || 0));
            await loadModalComments(postViewModal?.dataset.postId);
        }
    }

    function attachReplyForm(parentId, anchor) {
        if (!anchor) return;
        clearReplyForm();
        const form = document.createElement('form');
        form.className = 'reply-form';
        form.innerHTML = `
            <textarea placeholder="Write a reply..."></textarea>
            <button type="button">Reply</button>
        `;

        const textarea = form.querySelector('textarea');
        const button = form.querySelector('button');
        if (!textarea || !button) return;
        textarea.focus();
        button.addEventListener('click', () => submitReply(form, parentId));
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            submitReply(form, parentId);
        });

        anchor.appendChild(form);
        activeReplyForm = form;
    }

    if (postViewCommentsList) {
        postViewCommentsList.addEventListener('click', (event) => {
            const replyTrigger = event.target.closest('.comment-reply-btn');
            if (!replyTrigger) return;
            const parentId = replyTrigger.dataset.parentId;
            const commentBlock = replyTrigger.closest('.modal-comment');
            attachReplyForm(parentId, commentBlock);
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', closePostModal);
    }

    if (postViewOverlay) {
        postViewOverlay.addEventListener('click', closePostModal);
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', showPreviousPost);
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', showNextPost);
    }

    if (postViewModal) {
        postViewModal.addEventListener('click', (event) => {
            console.log('Modal click detected:', event.target);
            const upvoteTarget = event.target.closest('#postViewUpvoteBtn');
            if (upvoteTarget) {
                event.preventDefault();
                event.stopPropagation();
                handleModalVote('upvote');
                return;
            }

            const downvoteTarget = event.target.closest('#postViewDownvoteBtn');
            if (downvoteTarget) {
                event.preventDefault();
                event.stopPropagation();
                handleModalVote('downvote');
                return;
            }

            const commentToggleTarget = event.target.closest('#postViewCommentToggle');
            if (commentToggleTarget) {
                event.preventDefault();
                event.stopPropagation();
                if (postViewCommentsPanel) {
                    postViewCommentsPanel.classList.toggle('active');
                }
            }
        }, true);
    }

    if (postViewUpvoteBtn) {
        postViewUpvoteBtn.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            handleModalVote('upvote');
        });
    }

    if (postViewDownvoteBtn) {
        postViewDownvoteBtn.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            handleModalVote('downvote');
        });
    }

    if (postViewCommentForm) {
        postViewCommentForm.addEventListener('submit', submitModalComment);
    }

    document.addEventListener('keydown', function(e) {
        if (!postViewModal?.classList.contains('active')) return;

        switch(e.key) {
            case 'Escape':
                closePostModal();
                break;
            case 'ArrowLeft':
                showPreviousPost();
                break;
            case 'ArrowRight':
                showNextPost();
                break;
        }
    });

});