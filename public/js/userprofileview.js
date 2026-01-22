const postsPayloadElement = document.getElementById('profilePostPayload');
let parsedPostsData = { personal: [], group: [] };
if (postsPayloadElement) {
    try {
        parsedPostsData = JSON.parse(postsPayloadElement.textContent || '{}');
    } catch (error) {
        console.warn('Unable to parse profile post data:', error);
    }
}

window.PERSONAL_POSTS = parsedPostsData.personal || [];
window.GROUP_POSTS = parsedPostsData.group || [];

document.addEventListener('DOMContentLoaded', () => {
    const tabLinks = document.querySelectorAll('.profile-tabs a');
    const tabPanels = document.querySelectorAll('.group-content .tab-content');

    const setActiveTab = (target, options = {}) => {
        if (!target) return;

        let activeLink = null;
        tabLinks.forEach((link) => {
            const linkTarget = link.getAttribute('data-tab');
            const isActive = linkTarget === target;
            const parent = link.parentElement;
            if (parent) parent.classList.toggle('active', isActive);
            link.setAttribute('aria-selected', isActive ? 'true' : 'false');
            link.setAttribute('tabindex', isActive ? '0' : '-1');
            if (isActive) activeLink = link;
        });

        tabPanels.forEach((panel) => {
            const isActive = panel.id === `tab-${target}`;
            panel.classList.toggle('active', isActive);
        });

        if (options.scroll) {
            const activePanel = document.getElementById(`tab-${target}`);
            if (activePanel) {
                const offset = window.innerWidth <= 768 ? 64 : 96;
                const panelTop = activePanel.getBoundingClientRect().top + window.pageYOffset - offset;
                window.scrollTo({ top: panelTop < 0 ? 0 : panelTop, behavior: 'smooth' });
            }
            if (activeLink && typeof activeLink.focus === 'function') {
                try { activeLink.focus({ preventScroll: true }); } catch (error) { activeLink.focus(); }
            }
        }
    };

    tabLinks.forEach(link => {
        link.addEventListener('click', event => {
            event.preventDefault();
            const target = link.getAttribute('data-tab');
            setActiveTab(target, { scroll: true });
        });
    });

    const defaultActiveLink = document.querySelector('.profile-tabs li.active a');
    if (defaultActiveLink) setActiveTab(defaultActiveLink.getAttribute('data-tab'));

    const statTabButtons = document.querySelectorAll('[data-tab-target]');
    statTabButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const target = button.getAttribute('data-tab-target');
            setActiveTab(target, { scroll: true });
        });
    });

    const modal = document.getElementById('editProfileModal');
    const editProfileBtn = document.getElementById('editProfileBtn');
    const triggerEditCover = document.getElementById('triggerEditCover');
    const triggerEditAvatar = document.getElementById('triggerEditAvatar');
    const directAvatarInput = document.getElementById('directAvatarInput');
    const directCoverInput = document.getElementById('directCoverInput');
    const closeModalBtn = document.getElementById('closeEditProfileModal');
    const cancelBtn = document.getElementById('cancelEditProfileBtn');
    const form = document.getElementById('editProfileForm');
    const messageBox = document.getElementById('profileFormMessage');
    const profilePictureInput = document.getElementById('profilePictureInput');
    const coverPhotoInput = document.getElementById('coverPhotoInput');
    const profilePreview = document.getElementById('profilePicturePreview');
    const coverPreview = document.getElementById('coverPhotoPreview');
    const avatarDisplay = document.getElementById('profileAvatarImage');
    const coverDisplay = document.getElementById('profileCoverImage');
    const navAvatarImages = Array.from(document.querySelectorAll('#profileDropdown img'));
    const sidebarAvatarImages = Array.from(document.querySelectorAll('.profile-button .profile-picture img'));

    const openModal = () => {
        if (!modal) return;
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        if (messageBox) {
            messageBox.style.display = 'none';
            messageBox.textContent = '';
            messageBox.className = 'form-message';
        }
    };

    const closeModal = () => {
        if (!modal) return;
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    };

    if (editProfileBtn && modal) editProfileBtn.addEventListener('click', openModal);
    if (modal) {
        if (closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
        if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', event => { if (event.target === modal) closeModal(); });
    }

    const showMessage = (text, type) => {
        if (!messageBox) return;
        messageBox.textContent = text;
        messageBox.className = `form-message ${type}`;
        messageBox.style.display = 'block';
    };

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
        const icons = { success: 'uil-check-circle', error: 'uil-times-circle', info: 'uil-info-circle' };
        toast.innerHTML = `<i class="uil ${icons[type] || icons.success}"></i> ${message}`;
        toastContainer.appendChild(toast);
        setTimeout(() => { toast.classList.add('fade-out'); setTimeout(() => toast.remove(), 300); }, 3000);
    }

    const updatePreview = (input, preview, displayTarget) => {
        if (!input || !preview) return;
        input.addEventListener('change', event => {
            const file = event.target.files && event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = e => {
                if (preview) preview.src = e.target.result;
                if (displayTarget) displayTarget.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    };

    updatePreview(profilePictureInput, profilePreview, avatarDisplay);
    updatePreview(coverPhotoInput, coverPreview, coverDisplay);

    if (form) {
        form.addEventListener('submit', async event => {
            event.preventDefault();
            console.log('=== FORM SUBMIT STARTED ===');
            
            const formData = new FormData(form);
            formData.append('sub_action', 'update_profile');
            
            // Debug: Log what we're sending
            console.log('sub_action:', formData.get('sub_action'));
            console.log('first_name:', formData.get('first_name'));
            console.log('last_name:', formData.get('last_name'));
            console.log('email:', formData.get('email'));
            console.log('username:', formData.get('username'));
            console.log('has profile_picture file:', profilePictureInput?.files?.[0] ? 'YES' : 'NO');
            console.log('has cover_photo file:', coverPhotoInput?.files?.[0] ? 'YES' : 'NO');
            
            showMessage('Saving profile...', 'info');
            
            try {
                console.log('Sending request to:', BASE_PATH + 'index.php?controller=Profile&action=handleAjax');
                const response = await fetch(BASE_PATH + 'index.php?controller=Profile&action=handleAjax', { 
                    method: 'POST', 
                    body: formData 
                });
                
                console.log('Response status:', response.status);
                console.log('Response OK:', response.ok);
                
                const responseText = await response.text();
                console.log('Response text (first 500 chars):', responseText.substring(0, 500));
                
                let result;
                try {
                    result = JSON.parse(responseText);
                    console.log('Parsed result:', result);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    console.error('Full response text:', responseText);
                    showMessage('Server error: Invalid JSON response. Check console.', 'error');
                    return;
                }
                
                if (result.success) {
                    console.log('SUCCESS! Message:', result.message);
                    showMessage(result.message || 'Profile updated successfully!', 'success');
                    
                    // Update images if they were changed
                    if (result.profile_picture) {
                        console.log('Updating profile picture to:', result.profile_picture);
                        if (avatarDisplay) avatarDisplay.src = result.profile_picture;
                        if (profilePreview) profilePreview.src = result.profile_picture;
                        navAvatarImages.forEach(img => img.src = result.profile_picture);
                        sidebarAvatarImages.forEach(img => img.src = result.profile_picture);
                    }
                    if (result.cover_photo) {
                        console.log('Updating cover photo to:', result.cover_photo);
                        if (coverDisplay) coverDisplay.src = result.cover_photo;
                        if (coverPreview) coverPreview.src = result.cover_photo;
                    }
                    
                    // Close modal and reload
                    console.log('Closing modal and reloading in 800ms...');
                    setTimeout(() => {
                        if (modal) {
                            modal.classList.remove('active');
                            modal.setAttribute('aria-hidden', 'true');
                            document.body.style.overflow = '';
                        }
                        console.log('Reloading page...');
                        window.location.reload();
                    }, 800);
                } else {
                    console.error('FAILED! Message:', result.message);
                    showMessage(result.message || 'Failed to update profile.', 'error');
                }
            } catch (error) {
                console.error('EXCEPTION during profile update:', error);
                console.error('Error stack:', error.stack);
                showMessage('An unexpected error occurred. Check console for details.', 'error');
            }
            
            console.log('=== FORM SUBMIT ENDED ===');
        });
    }

    const setLoadingState = (button, isLoading, loadingContent) => {
        if (!button) return;
        if (!button.dataset.originalContent) button.dataset.originalContent = button.innerHTML;
        if (isLoading) {
            button.disabled = true;
            button.classList.add('uploading');
            button.setAttribute('aria-busy', 'true');
            if (loadingContent) button.innerHTML = loadingContent;
        } else {
            button.disabled = false;
            button.classList.remove('uploading');
            button.removeAttribute('aria-busy');
            if (button.dataset.originalContent) button.innerHTML = button.dataset.originalContent;
        }
    };

    const flashSuccess = (button, successContent) => {
        if (!button || !successContent) return;
        const original = button.dataset.originalContent || button.innerHTML;
        button.innerHTML = successContent;
        setTimeout(() => button.innerHTML = original, 1500);
    };

    const handleDirectUpload = ({ input, endpoint, trigger, loadingContent, successContent, responseKey = 'image_url', previewTargets = [], fileField }) => {
        if (!input || !trigger) return;
        input.addEventListener('change', async event => {
            const file = event.target.files && event.target.files[0];
            if (!file) return;
            const formData = new FormData();
            formData.append(fileField || input.name || 'file', file);
            formData.append('sub_action', endpoint);
            let wasSuccessful = false;
            setLoadingState(trigger, true, loadingContent);
            try {
                const response = await fetch(BASE_PATH + 'index.php?controller=Profile&action=handleAjax', { method: 'POST', body: formData });
                if (!response.ok) throw new Error(`Request failed with status ${response.status}`);
                const result = await response.json();
                if (result.success && result[responseKey]) {
                    const mediaUrl = result[responseKey];
                    previewTargets.forEach(target => { if (target && mediaUrl) target.src = mediaUrl; });
                    wasSuccessful = true;
                } else {
                    showToast(result.message || 'Unable to update image.', 'error');
                }
            } catch (error) {
                console.error(`Failed to update ${responseKey}:`, error);
                showToast('An unexpected error occurred while uploading the image.', 'error');
            } finally {
                setLoadingState(trigger, false);
                if (wasSuccessful) flashSuccess(trigger, successContent);
                if (input) input.value = '';
            }
        });
    };

    if (triggerEditAvatar && directAvatarInput) {
        triggerEditAvatar.addEventListener('click', () => directAvatarInput.click());
        handleDirectUpload({
            input: directAvatarInput,
            endpoint: 'upload_avatar',
            fileField: 'avatar',
            trigger: triggerEditAvatar,
            loadingContent: '<i class="uil uil-cloud-upload"></i>',
            successContent: '<i class="uil uil-check"></i>',
            responseKey: 'image_url',
            previewTargets: [avatarDisplay, profilePreview, ...navAvatarImages, ...sidebarAvatarImages]
        });
    }

    if (triggerEditCover && directCoverInput) {
        triggerEditCover.addEventListener('click', () => directCoverInput.click());
        handleDirectUpload({
            input: directCoverInput,
            endpoint: 'upload_cover',
            fileField: 'cover',
            trigger: triggerEditCover,
            loadingContent: '<i class="uil uil-cloud-upload"></i> Uploading...',
            successContent: '<i class="uil uil-check"></i> Saved',
            responseKey: 'image_url',
            previewTargets: [coverDisplay, coverPreview]
        });
    }

    const friendListModal = document.getElementById('friendListModal');
    let lastFriendTrigger = null;

    const friendTriggers = document.querySelectorAll('[data-friend-count-trigger]');

    const openFriendListModal = (trigger) => {
        if (!friendListModal) return;
        lastFriendTrigger = trigger || null;
        friendListModal.classList.add('active');
        friendListModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        const closeButton = friendListModal.querySelector('[data-close-friends-modal]');
        if (closeButton) setTimeout(() => closeButton.focus(), 10);
    };

    const closeFriendListModal = () => {
        if (!friendListModal) return;
        friendListModal.classList.remove('active');
        friendListModal.setAttribute('aria-hidden', 'true');
        const editModalIsOpen = document.querySelector('.profile-edit-modal.active');
        if (!editModalIsOpen) document.body.style.overflow = '';
        if (lastFriendTrigger && typeof lastFriendTrigger.focus === 'function') lastFriendTrigger.focus();
        lastFriendTrigger = null;
    };

    if (friendListModal) {
        const closeTargets = friendListModal.querySelectorAll('[data-close-friends-modal]');
        closeTargets.forEach((element) => {
            element.addEventListener('click', (event) => {
                event.preventDefault();
                closeFriendListModal();
            });
        });
        friendListModal.addEventListener('click', (event) => { if (event.target === friendListModal) closeFriendListModal(); });
        document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && friendListModal.classList.contains('active')) closeFriendListModal(); });
    }

    if (friendTriggers.length) {
        friendTriggers.forEach((trigger) => {
            trigger.addEventListener('click', (event) => {
                event.preventDefault();
                openFriendListModal(trigger);
            });
            trigger.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openFriendListModal(trigger);
                }
            });
        });
    }

    // Instagram-Style Post Modal
    const postViewModal = document.getElementById('postViewModal');
    const postViewClose = document.querySelector('.post-view-close');
    const postViewOverlay = document.querySelector('.post-view-overlay');
    const postViewPrev = document.querySelector('.post-view-prev');
    const postViewNext = document.querySelector('.post-view-next');
    const postViewImage = document.getElementById('postViewImage');
    const postViewTextContent = document.getElementById('postViewTextContent');
    const postViewAvatar = document.getElementById('postViewAvatar');
    const postViewUsername = document.getElementById('postViewUsername');
    const postViewDate = document.getElementById('postViewDate');
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

    let currentPostIndex = 0;
    let currentPostType = 'personal';
    let currentPosts = [];

    function focusModalCommentsPanel() {
        if (postViewCommentsPanel && !postViewCommentsPanel.classList.contains('active')) {
            postViewCommentsPanel.classList.add('active');
        }
        if (postViewCommentsPanel) {
            postViewCommentsPanel.scrollTop = postViewCommentsPanel.scrollHeight;
        }
        if (postViewCommentInput) {
            postViewCommentInput.focus({ preventScroll: false });
        }
    }

    function openPostModal(index, type) {
        currentPostIndex = index;
        currentPostType = type;
        currentPosts = type === 'personal' ? (window.PERSONAL_POSTS || []) : (window.GROUP_POSTS || []);
        
        if (!currentPosts || currentPosts.length === 0) return;
        
        displayPost(currentPostIndex);
        postViewModal.classList.add('active');
        postViewModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        
        // Update navigation buttons
        updateNavigationButtons();
    }

    function openPostModalByPostId(postId, type) {
        const posts = type === 'personal' ? (window.PERSONAL_POSTS || []) : (window.GROUP_POSTS || []);
        const index = posts.findIndex(post => parseInt(post.post_id) === postId);
        
        if (index !== -1) {
            // Switch to appropriate tab first
            const targetTab = type === 'personal' ? 'posts' : 'group-posts';
            setActiveTab(targetTab);
            
            // Small delay to ensure tab content is rendered
            setTimeout(() => {
                openPostModal(index, type);
            }, 50);
        } else {
            console.warn(`Post with ID ${postId} not found in ${type} posts`);
        }
    }

    function closePostModal() {
        clearReplyForm();
        postViewModal.classList.remove('active');
        postViewModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    function displayPost(index) {
        if (!currentPosts || index < 0 || index >= currentPosts.length) return;
        
        const post = currentPosts[index];
        
        // Handle image or text display
        if (post.image_url) {
            postViewImage.src = post.image_url;
            postViewImage.style.display = 'block';
            postViewTextContent.style.display = 'none';
            postViewTextContent.classList.remove('active');
        } else {
            postViewImage.style.display = 'none';
            postViewTextContent.textContent = post.content || '';
            postViewTextContent.style.display = 'flex';
            postViewTextContent.classList.add('active');
        }
        
        // Set user info
        const avatarUrl = post.profile_picture || (BASE_PATH + 'images/avatars/defaultProfilePic.png');
        postViewAvatar.src = avatarUrl;
        
        const fullName = (post.first_name || '') + ' ' + (post.last_name || '');
        const displayName = fullName.trim() || post.username || 'Unknown User';
        postViewUsername.textContent = displayName;
        
        postViewDate.textContent = post.created_at || '';
        
        // Set caption
        postViewCaption.textContent = post.content || '';

        // Set vote and comment counts
        if (postViewUpvoteCount) postViewUpvoteCount.textContent = post.upvote_count ?? '0';
        if (postViewDownvoteCount) postViewDownvoteCount.textContent = post.downvote_count ?? '0';
        if (postViewCommentCount) postViewCommentCount.textContent = post.comment_count ?? '0';
        if (postViewCommentBadge) postViewCommentBadge.textContent = post.comment_count ?? '0';

        // Highlight previous vote
        updateModalVoteState(post.user_vote ? post.user_vote : null, 'init');

        // Set current post id for the modal
        if (postViewModal) postViewModal.dataset.postId = post.post_id || post.postId || '';

        // Load modal comments for this post
        loadModalComments(post.post_id || post.postId);
    }

    function updateModalVoteState(voteType, action = '') {
        if (!postViewUpvoteBtn || !postViewDownvoteBtn) return;
        postViewUpvoteBtn.classList.remove('liked');
        postViewDownvoteBtn.classList.remove('liked');

        if (!voteType) return;
        if (action === 'removed') return;

        if (voteType === 'upvote') {
            postViewUpvoteBtn.classList.add('liked');
        } else if (voteType === 'downvote') {
            postViewDownvoteBtn.classList.add('liked');
        }
    }

    async function handleModalVote(voteType) {
        const postId = postViewModal?.dataset.postId;
        if (!postId) return;

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
                    <button type="button" class="comment-reply-btn" data-parent-id="${commentId}">
                        <i class="uil uil-reply"></i>
                        <span>Reply</span>
                    </button>
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
        console.log('attachReplyForm called - parentId:', parentId, 'anchor:', anchor);
        if (!anchor) {
            console.error('No anchor element provided');
            return;
        }
        clearReplyForm();
        const form = document.createElement('form');
        form.className = 'reply-form';
        form.innerHTML = `
            <textarea placeholder="Write a reply..." rows="3"></textarea>
            <button type="button">Reply</button>
        `;

        const textarea = form.querySelector('textarea');
        const button = form.querySelector('button');
        if (!textarea || !button) {
            console.error('Textarea or button not found in form');
            return;
        }
        
        anchor.appendChild(form);
        activeReplyForm = form;
        console.log('Reply form appended, trying to focus textarea');
        
        // Use setTimeout to ensure DOM is updated before focusing
        setTimeout(() => {
            textarea.focus();
            console.log('Textarea focused, form should be visible');
        }, 10);
        
        button.addEventListener('click', () => submitReply(form, parentId));
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            submitReply(form, parentId);
        });
    }

    if (postViewCommentsList) {
        postViewCommentsList.addEventListener('click', (event) => {
            console.log('Comment list clicked:', event.target);
            const replyTrigger = event.target.closest('.comment-reply-btn');
            console.log('Reply trigger found:', replyTrigger);
            if (!replyTrigger) return;
            event.preventDefault();
            event.stopPropagation();
            const parentId = replyTrigger.dataset.parentId;
            const commentBlock = replyTrigger.closest('.modal-comment');
            console.log('Attaching reply form for comment:', parentId);
            attachReplyForm(parentId, commentBlock);
        });
    }

    function updateNavigationButtons() {
        if (currentPostIndex <= 0) {
            postViewPrev.classList.add('hidden');
        } else {
            postViewPrev.classList.remove('hidden');
        }
        
        if (currentPostIndex >= currentPosts.length - 1) {
            postViewNext.classList.add('hidden');
        } else {
            postViewNext.classList.remove('hidden');
        }
    }

    function showPrevPost() {
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

    // Event listeners for modal
    if (postViewClose) {
        postViewClose.addEventListener('click', closePostModal);
    }

    if (postViewOverlay) {
        postViewOverlay.addEventListener('click', closePostModal);
    }

    if (postViewPrev) {
        postViewPrev.addEventListener('click', showPrevPost);
    }

    if (postViewNext) {
        postViewNext.addEventListener('click', showNextPost);
    }

    if (postViewUpvoteBtn) {
        postViewUpvoteBtn.addEventListener('click', () => handleModalVote('upvote'));
    }

    if (postViewDownvoteBtn) {
        postViewDownvoteBtn.addEventListener('click', () => handleModalVote('downvote'));
    }

    if (postViewCommentForm) {
        postViewCommentForm.addEventListener('submit', submitModalComment);
    }

    if (postViewCommentToggle) {
        postViewCommentToggle.addEventListener('click', () => {
            if (!postViewCommentsPanel) return;
            postViewCommentsPanel.classList.toggle('active');
        });
    }

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (postViewModal && postViewModal.classList.contains('active')) {
            if (e.key === 'Escape') {
                closePostModal();
            } else if (e.key === 'ArrowLeft') {
                showPrevPost();
            } else if (e.key === 'ArrowRight') {
                showNextPost();
            }
        }
    });

    // Click handlers for post grid items
    document.addEventListener('click', (e) => {
        const postGridItem = e.target.closest('.post-grid-item');
        if (postGridItem && postGridItem.hasAttribute('data-post-index')) {
            e.preventDefault();
            e.stopPropagation();
            const index = parseInt(postGridItem.getAttribute('data-post-index'));
            const type = postGridItem.getAttribute('data-post-type') || 'personal';
            openPostModal(index, type);
        }
    });

    function handleInitialHashNavigation() {
        if (!window.location.hash) return;
        let hash = window.location.hash.substring(1);
        const wantsComments = hash.endsWith('-comments');
        if (wantsComments) {
            hash = hash.replace(/-comments$/, '');
        }

        if (hash.startsWith('personal-post-') || hash.startsWith('group-post-')) {
            const parts = hash.split('-');
            const postId = parseInt(parts[parts.length - 1], 10);
            const postType = hash.startsWith('personal-post-') ? 'personal' : 'group';

            setTimeout(() => {
                openPostModalByPostId(postId, postType);
                if (wantsComments) {
                    setTimeout(() => focusModalCommentsPanel(), 200);
                }
            }, 100);
        } else {
            const correspondingTab = document.querySelector(`.profile-tabs a[data-tab="${hash}"]`);
            if (correspondingTab) {
                correspondingTab.click();
            }
        }
    }

    handleInitialHashNavigation();
    window.addEventListener('hashchange', handleInitialHashNavigation);

});
