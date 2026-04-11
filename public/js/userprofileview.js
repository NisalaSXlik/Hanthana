const postsPayloadElement = document.getElementById('profilePostPayload');
let parsedPostsData = { personal: [], group: [], saved: [] };
if (postsPayloadElement) {
    try {
        parsedPostsData = JSON.parse(postsPayloadElement.textContent || '{}');
    } catch (error) {
        console.warn('Unable to parse profile post data:', error);
    }
}

window.PERSONAL_POSTS = parsedPostsData.personal || [];
window.GROUP_POSTS = parsedPostsData.group || [];
window.SAVED_POSTS = parsedPostsData.saved || [];

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
    const emailInput = document.getElementById('emailInput');
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

    const isValidUniversityEmail = (email) => {
        return /^[^@\s]+@[a-z0-9-]+(?:\.[a-z0-9-]+)*\.ac\.lk$/i.test(String(email || '').trim());
    };

    const validateProfileEmailInput = () => {
        if (!emailInput) return true;

        const email = emailInput.value.trim();
        if (!email) {
            emailInput.setCustomValidity('Email is required.');
            return false;
        }

        if (!isValidUniversityEmail(email)) {
            emailInput.setCustomValidity('Use university email ending with .ac.lk');
            return false;
        }

        emailInput.setCustomValidity('');
        return true;
    };

    if (emailInput) {
        emailInput.addEventListener('input', validateProfileEmailInput);
        emailInput.addEventListener('blur', validateProfileEmailInput);
    }

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

    const profileMore = document.querySelector('[data-profile-more]');
    const profileMoreTrigger = profileMore ? profileMore.querySelector('[data-profile-more-trigger]') : null;
    const profileMoreMenu = profileMore ? profileMore.querySelector('[data-profile-more-menu]') : null;
    const blockUserBtn = profileMore ? profileMore.querySelector('[data-profile-block-user]') : null;

    const closeProfileMoreMenu = () => {
        if (!profileMoreTrigger || !profileMoreMenu) return;
        profileMoreMenu.hidden = true;
        profileMoreTrigger.setAttribute('aria-expanded', 'false');
    };

    const openProfileMoreMenu = () => {
        if (!profileMoreTrigger || !profileMoreMenu) return;
        profileMoreMenu.hidden = false;
        profileMoreTrigger.setAttribute('aria-expanded', 'true');
    };

    if (profileMoreTrigger && profileMoreMenu) {
        profileMoreTrigger.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            const isOpen = profileMoreTrigger.getAttribute('aria-expanded') === 'true';
            if (isOpen) {
                closeProfileMoreMenu();
            } else {
                openProfileMoreMenu();
            }
        });

        profileMoreMenu.addEventListener('click', (event) => {
            if (event.target.closest('.profile-more-item')) {
                closeProfileMoreMenu();
            }
        });

        document.addEventListener('click', (event) => {
            if (!profileMore.contains(event.target)) {
                closeProfileMoreMenu();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeProfileMoreMenu();
            }
        });
    }

    const setBlockedStateOnFriendButtons = (targetUserId) => {
        if (!targetUserId) return;

        if (typeof window.__hanthanaUpdateFriendButtonState === 'function') {
            window.__hanthanaUpdateFriendButtonState(targetUserId, 'blocked');
            return;
        }

        const selectors = `.add-friend-btn[data-user-id="${targetUserId}"]`;
        document.querySelectorAll(selectors).forEach((button) => {
            button.dataset.state = 'blocked';
            button.disabled = true;
            const textElement = button.querySelector('span');
            if (textElement) {
                textElement.textContent = 'Unavailable';
            } else {
                button.textContent = 'Unavailable';
            }
        });
    };

    if (blockUserBtn) {
        blockUserBtn.addEventListener('click', async (event) => {
            event.preventDefault();

            const targetUserId = parseInt(blockUserBtn.dataset.userId || '0', 10);
            const targetLabel = (blockUserBtn.dataset.userLabel || 'this user').trim();
            if (!targetUserId) {
                showToast('Unable to block this user right now.', 'error');
                return;
            }

            if (!window.confirm(`Block ${targetLabel}? You will no longer interact with this account.`)) {
                return;
            }

            const originalHtml = blockUserBtn.innerHTML;
            blockUserBtn.disabled = true;
            blockUserBtn.innerHTML = '<i class="uil uil-spinner-alt"></i><span>Blocking...</span>';

            try {
                const response = await fetch(BASE_PATH + 'index.php?controller=Settings&action=blockUser', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `user_id=${encodeURIComponent(targetUserId)}`,
                });

                const responseText = await response.text();
                let data = null;
                try {
                    data = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('Block user parse error:', parseError, responseText);
                }

                if (!response.ok || !data || !data.success) {
                    throw new Error((data && data.message) ? data.message : 'Unable to block this user right now.');
                }

                setBlockedStateOnFriendButtons(targetUserId);
                blockUserBtn.innerHTML = '<i class="uil uil-ban"></i><span>Blocked</span>';
                blockUserBtn.disabled = true;
                showToast(data.message || 'User blocked successfully.', 'success');
            } catch (error) {
                console.error('Block user failed:', error);
                blockUserBtn.innerHTML = originalHtml;
                blockUserBtn.disabled = false;
                showToast(error?.message || 'Unable to block this user right now.', 'error');
            }
        });
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

            if (!validateProfileEmailInput()) {
                emailInput?.reportValidity();
                return;
            }
            
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
            successContent: '<i class="uis uis-bookmark"></i> Saved',
            responseKey: 'image_url',
            previewTargets: [coverDisplay, coverPreview]
        });
    }

    const friendListModal = document.getElementById('friendListModal');
    const groupListModal = document.getElementById('groupListModal');
    let lastFriendTrigger = null;
    let lastGroupTrigger = null;

    const friendTriggers = document.querySelectorAll('[data-friend-count-trigger]');
    const groupTriggers = document.querySelectorAll('[data-group-count-trigger]');

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

    const openGroupListModal = (trigger) => {
        if (!groupListModal) return;
        lastGroupTrigger = trigger || null;
        groupListModal.classList.add('active');
        groupListModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        const closeButton = groupListModal.querySelector('[data-close-groups-modal]');
        if (closeButton) setTimeout(() => closeButton.focus(), 10);
    };

    const closeGroupListModal = () => {
        if (!groupListModal) return;
        groupListModal.classList.remove('active');
        groupListModal.setAttribute('aria-hidden', 'true');
        const editModalIsOpen = document.querySelector('.profile-edit-modal.active');
        if (!editModalIsOpen) document.body.style.overflow = '';
        if (lastGroupTrigger && typeof lastGroupTrigger.focus === 'function') lastGroupTrigger.focus();
        lastGroupTrigger = null;
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

    if (groupListModal) {
        const closeTargets = groupListModal.querySelectorAll('[data-close-groups-modal]');
        closeTargets.forEach((element) => {
            element.addEventListener('click', (event) => {
                event.preventDefault();
                closeGroupListModal();
            });
        });
        groupListModal.addEventListener('click', (event) => { if (event.target === groupListModal) closeGroupListModal(); });
        document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && groupListModal.classList.contains('active')) closeGroupListModal(); });
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

    if (groupTriggers.length) {
        groupTriggers.forEach((trigger) => {
            trigger.addEventListener('click', (event) => {
                event.preventDefault();
                openGroupListModal(trigger);
            });
            trigger.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openGroupListModal(trigger);
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
    const postViewMenu = document.getElementById('postViewMenu');
    const postViewMenuTrigger = document.getElementById('postViewMenuTrigger');
    const postViewMenuDropdown = document.getElementById('postViewMenuDropdown');
    const postViewEditAction = document.getElementById('postViewEditAction');
    const postViewDeleteAction = document.getElementById('postViewDeleteAction');
    const postViewReportAction = document.getElementById('postViewReportAction');

    let currentPostIndex = 0;
    let currentPostType = 'personal';
    let currentPosts = [];
    let modalCommentsMeta = { currentUserId: null, postOwnerId: null };
    const resolvedViewerUserId = Number(
        (typeof VIEWER_USER_ID !== 'undefined'
            ? VIEWER_USER_ID
            : (window.VIEWER_USER_ID ?? window.CURRENT_USER_ID ?? 0)) || 0
    );
    const resolvedProfileUserId = Number(
        (typeof PROFILE_USER_ID !== 'undefined'
            ? PROFILE_USER_ID
            : (window.PROFILE_USER_ID ?? 0)) || 0
    );
    const resolvedIsOwnerProfile = String(
        (typeof IS_OWNER !== 'undefined'
            ? IS_OWNER
            : (window.IS_OWNER ?? 'false'))
    ) === 'true';

    function getPostsCollection(type) {
        if (type === 'group') return window.GROUP_POSTS || [];
        if (type === 'saved') return window.SAVED_POSTS || [];
        return window.PERSONAL_POSTS || [];
    }

    function resolvePostOwnerId(post) {
        return Number(
            post?.author_id
            ?? post?.user_id
            ?? post?.authorId
            ?? post?.userId
            ?? resolvedProfileUserId
            ?? 0
        ) || 0;
    }

    function canManagePost(post) {
        if (resolvedIsOwnerProfile && (currentPostType === 'personal' || currentPostType === 'group')) {
            return true;
        }

        const ownerId = resolvePostOwnerId(post);
        if (ownerId > 0 && resolvedViewerUserId > 0) {
            return ownerId === resolvedViewerUserId;
        }

        if (ownerId > 0 && resolvedProfileUserId > 0 && resolvedIsOwnerProfile) {
            return ownerId === resolvedProfileUserId;
        }

        return false;
    }

    function isPostViewMenuOpen() {
        return !!postViewMenuDropdown && postViewMenuDropdown.hidden === false;
    }

    function closePostViewMenu() {
        if (!postViewMenuDropdown || !postViewMenuTrigger) return;
        postViewMenuDropdown.hidden = true;
        postViewMenuTrigger.setAttribute('aria-expanded', 'false');
    }

    function openPostViewMenu() {
        if (!postViewMenuDropdown || !postViewMenuTrigger) return;
        postViewMenuDropdown.hidden = false;
        postViewMenuTrigger.setAttribute('aria-expanded', 'true');
    }

    function syncPostViewMenu(post) {
        if (!postViewMenu || !postViewReportAction || !postViewEditAction || !postViewDeleteAction) return;

        const postId = Number(post?.post_id || post?.postId || 0);
        const ownsPost = canManagePost(post);

        postViewMenu.hidden = false;
        postViewEditAction.hidden = !ownsPost;
        postViewDeleteAction.hidden = !ownsPost;
        postViewReportAction.hidden = ownsPost;
        postViewReportAction.classList.toggle('is-hidden', ownsPost);

        postViewReportAction.dataset.targetId = postId ? String(postId) : '';
        postViewReportAction.dataset.targetLabel = `${(post?.username || 'user')} post`;

        closePostViewMenu();
    }

    async function updateCurrentPostContent(newContent) {
        const post = currentPosts[currentPostIndex];
        const postId = Number(post?.post_id || post?.postId || 0);
        if (!postId) return false;

        const formData = new FormData();
        formData.append('sub_action', 'update');
        formData.append('post_id', String(postId));
        formData.append('content', newContent);

        const response = await fetch(BASE_PATH + 'index.php?controller=Posts&action=handleAjax', {
            method: 'POST',
            body: formData,
        });
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Unable to update this post.');
        }

        const collection = getPostsCollection(currentPostType);
        if (collection[currentPostIndex]) {
            collection[currentPostIndex].content = newContent;
        }
        if (currentPosts[currentPostIndex]) {
            currentPosts[currentPostIndex].content = newContent;
        }

        return true;
    }

    async function deleteCurrentPost() {
        const post = currentPosts[currentPostIndex];
        const postId = Number(post?.post_id || post?.postId || 0);
        if (!postId) return;

        const formData = new FormData();
        formData.append('sub_action', 'delete');
        formData.append('post_id', String(postId));

        const response = await fetch(BASE_PATH + 'index.php?controller=Posts&action=handleAjax', {
            method: 'POST',
            body: formData,
        });
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Unable to delete this post.');
        }

        const collection = getPostsCollection(currentPostType);
        collection.splice(currentPostIndex, 1);
        currentPosts = collection;

        const selector = `.post-grid-item[data-post-type="${currentPostType === 'group' ? 'group' : (currentPostType === 'saved' ? 'saved' : 'personal')}"]`;
        const gridItems = Array.from(document.querySelectorAll(selector));
        const toRemove = gridItems[currentPostIndex];
        if (toRemove) {
            toRemove.remove();
        }

        Array.from(document.querySelectorAll(selector)).forEach((item, index) => {
            item.setAttribute('data-post-index', String(index));
        });

        if (!currentPosts.length) {
            closePostModal();
            return;
        }

        if (currentPostIndex >= currentPosts.length) {
            currentPostIndex = currentPosts.length - 1;
        }
        displayPost(currentPostIndex);
        updateNavigationButtons();
    }

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
        const normalizedIndex = Number(index);
        if (!Number.isInteger(normalizedIndex) || normalizedIndex < 0) {
            return;
        }

        currentPostIndex = normalizedIndex;
        currentPostType = type;
        if (type === 'group') {
            currentPosts = window.GROUP_POSTS || [];
        } else if (type === 'saved') {
            currentPosts = window.SAVED_POSTS || [];
        } else {
            currentPosts = window.PERSONAL_POSTS || [];
        }
        
        if (!currentPosts || currentPosts.length === 0) return;
        if (currentPostIndex >= currentPosts.length) return;

        try {
            displayPost(currentPostIndex);
        } catch (error) {
            console.error('Failed to open post modal:', error);
            return;
        }

        postViewModal.classList.add('active');
        postViewModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        
        // Update navigation buttons
        updateNavigationButtons();
    }

    function openPostModalByPostId(postId, type) {
        let posts = [];
        if (type === 'group') {
            posts = window.GROUP_POSTS || [];
        } else if (type === 'saved') {
            posts = window.SAVED_POSTS || [];
        } else {
            posts = window.PERSONAL_POSTS || [];
        }
        const index = posts.findIndex(post => parseInt(post.post_id) === postId);
        
        if (index !== -1) {
            // Switch to appropriate tab first
            const targetTab = type === 'group' ? 'group-posts' : (type === 'saved' ? 'saved' : 'posts');
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
        closePostViewMenu();
        postViewModal.classList.remove('active');
        postViewModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    function normalizePostMetadata(metadata) {
        if (!metadata) {
            return {};
        }

        if (typeof metadata === 'string') {
            try {
                const parsed = JSON.parse(metadata);
                return parsed && typeof parsed === 'object' ? parsed : {};
            } catch (error) {
                return {};
            }
        }

        return typeof metadata === 'object' ? metadata : {};
    }

    function resolveModalPostType(post) {
        const isGroupPost = Number(post?.is_group_post || 0) === 1 || !!post?.group_id;
        const rawType = isGroupPost
            ? (post?.group_post_type || post?.post_type || 'discussion')
            : (post?.post_type || post?.group_post_type || 'discussion');

        const normalized = String(rawType || '').toLowerCase();
        if (!normalized || normalized === 'text' || normalized === 'image' || normalized === 'general') {
            return 'discussion';
        }
        return normalized;
    }

    function formatMultilineText(value) {
        if (!value) {
            return '';
        }
        return escapeHtml(String(value)).replace(/\n/g, '<br>');
    }

    function formatModalEventDate(dateValue) {
        if (!dateValue) {
            return '';
        }
        const parsed = new Date(dateValue);
        if (Number.isNaN(parsed.getTime())) {
            return escapeHtml(String(dateValue));
        }
        return parsed.toLocaleDateString(undefined, {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    }

    function resolveResourceDownloadUrl(pathValue) {
        if (!pathValue) {
            return '';
        }

        const raw = String(pathValue).trim();
        if (!raw) {
            return '';
        }

        if (/^https?:\/\//i.test(raw)) {
            return raw;
        }

        const basePath = BASE_PATH.endsWith('/') ? BASE_PATH : `${BASE_PATH}/`;
        return `${basePath}${raw.replace(/^\/+/, '')}`;
    }

    function renderDiscussionPostMarkup(post) {
        const content = (post?.content || '').trim();
        const imageUrl = post?.image_url || '';

        if (!imageUrl && content) {
            return {
                mode: 'simple-text',
                html: `<p>${formatMultilineText(content)}</p>`,
            };
        }

        if (imageUrl && !content) {
            return {
                mode: 'image-only',
                html: '',
                imageUrl,
            };
        }

        const textBlock = content
            ? `<p class="post-text modal-post-paragraph">${formatMultilineText(content)}</p>`
            : '';

        const imageBlock = imageUrl
            ? `
                <div class="photo post-image modal-type-image-wrap">
                    <img class="modal-type-image" src="${escapeHtml(imageUrl)}" alt="Post image">
                </div>
            `
            : '';

        return {
            mode: 'rich',
            html: `
                <div class="modal-post-content modal-post-discussion">
                    ${textBlock || '<p class="modal-post-empty">No content available for this post.</p>'}
                    ${imageBlock}
                </div>
            `,
        };
    }

    function renderQuestionPostMarkup(post, metadata) {
        const content = (post?.content || '').trim();
        const category = (metadata?.category || '').trim();
        const tags = Array.isArray(metadata?.tags)
            ? metadata.tags
            : (typeof metadata?.tags === 'string' ? metadata.tags.split(',').map(tag => tag.trim()).filter(Boolean) : []);

        const categoryBlock = category
            ? `<span class="question-category">${escapeHtml(category)}</span>`
            : '';

        const textBlock = content
            ? `<p class="post-text modal-post-paragraph">${formatMultilineText(content)}</p>`
            : '<p class="modal-post-empty">Question details are unavailable.</p>';

        const tagsBlock = tags.length
            ? `<div class="question-tags">${tags.map(tag => `<span class="tag">${escapeHtml(tag)}</span>`).join('')}</div>`
            : '';

        const imageBlock = post?.image_url
            ? `
                <div class="photo post-image modal-type-image-wrap">
                    <img class="modal-type-image" src="${escapeHtml(post.image_url)}" alt="Question image">
                </div>
            `
            : '';

        return {
            mode: 'rich',
            html: `
                <div class="modal-post-content modal-post-question">
                    <div class="question-content">
                        ${categoryBlock}
                        ${textBlock}
                        ${tagsBlock}
                    </div>
                    ${imageBlock}
                </div>
            `,
        };
    }

    function renderResourcePostMarkup(post, metadata) {
        const title = (metadata?.title || 'Untitled Resource').trim();
        const resourceType = (metadata?.resource_type || metadata?.type || '').trim();
        const resourceLink = (metadata?.resource_link || metadata?.link || '').trim();
        const downloadUrl = resolveResourceDownloadUrl(metadata?.file_path || '');
        const content = (post?.content || '').trim();

        const typeBlock = resourceType
            ? `<span class="resource-type-label">${escapeHtml(resourceType)}</span>`
            : '';

        const descriptionBlock = content
            ? `<p class="post-text modal-post-paragraph">${formatMultilineText(content)}</p>`
            : '';

        const actions = [];
        if (downloadUrl) {
            actions.push(`
                <a href="${escapeHtml(downloadUrl)}" class="resource-download" download target="_blank" rel="noopener noreferrer">
                    <i class="uil uil-download-alt"></i>
                    <span>Download</span>
                </a>
            `);
        }
        if (resourceLink) {
            actions.push(`
                <a href="${escapeHtml(resourceLink)}" class="resource-link" target="_blank" rel="noopener noreferrer">
                    <i class="uil uil-external-link-alt"></i>
                    <span>Open Link</span>
                </a>
            `);
        }

        return {
            mode: 'rich',
            html: `
                <div class="modal-post-content modal-post-resource">
                    <div class="resource-content">
                        <h3 class="resource-title">${escapeHtml(title)}</h3>
                        ${typeBlock}
                        ${descriptionBlock || '<p class="modal-post-empty">No description provided for this resource.</p>'}
                        ${actions.length ? `<div class="resource-actions">${actions.join('')}</div>` : ''}
                    </div>
                </div>
            `,
        };
    }

    function renderPollPostMarkup(post, metadata) {
        const isGroupPoll = Number(post?.is_group_post || 0) === 1 || !!post?.group_id;
        const options = Array.isArray(metadata?.options) ? metadata.options : [];
        const votesRaw = Array.isArray(metadata?.votes) ? metadata.votes : [];
        const votes = options.map((_, index) => Number(votesRaw[index] || 0));
        const totalVotes = votes.reduce((sum, value) => sum + value, 0);
        const selectedVote = Number.isInteger(Number(post?.user_poll_vote)) ? Number(post.user_poll_vote) : -1;
        const hasVoted = selectedVote >= 0;

        const optionsMarkup = options.length
            ? options.map((optionText, index) => {
                const voteCount = Number(votes[index] || 0);
                const percentage = totalVotes > 0 ? Math.round((voteCount / totalVotes) * 100) : 0;
                const selectedClass = hasVoted && selectedVote === index ? 'selected' : '';
                return `
                    <div class="poll-option ${selectedClass}" data-option-index="${index}">
                        <button class="poll-option-btn" type="button" aria-label="Vote for ${escapeHtml(String(optionText))}" ${isGroupPoll ? '' : 'disabled'}>
                            <span class="option-text">${escapeHtml(String(optionText))}</span>
                            <div class="option-stats">
                                <span class="option-percentage">${percentage}%</span>
                                <span class="option-votes">${voteCount} vote${voteCount === 1 ? '' : 's'}</span>
                            </div>
                            <div class="option-progress" style="width: ${percentage}%"></div>
                        </button>
                    </div>
                `;
            }).join('')
            : '<p class="modal-post-empty">Poll options are unavailable for this post.</p>';

        const durationMarkup = metadata?.duration
            ? `<span class="poll-duration">Ends in ${escapeHtml(String(metadata.duration))} days</span>`
            : '';

        const pollMetaNote = isGroupPoll
            ? durationMarkup
            : '<span class="poll-duration">Voting is available from the group post view.</span>';

        return {
            mode: 'rich',
            html: `
                <div class="modal-post-content modal-post-poll">
                    <div class="poll-content">
                        ${post?.content ? `<p class="post-text poll-question">${formatMultilineText(post.content)}</p>` : ''}
                        <div class="poll-options" data-post-id="${escapeHtml(String(post?.post_id || ''))}">
                            ${optionsMarkup}
                        </div>
                        <div class="poll-footer" data-post-id="${escapeHtml(String(post?.post_id || ''))}">
                            <button type="button" class="poll-total-votes" data-post-id="${escapeHtml(String(post?.post_id || ''))}">
                                <i class="uil uil-users-alt"></i>
                                <span>${totalVotes} total vote${totalVotes === 1 ? '' : 's'}</span>
                            </button>
                            ${pollMetaNote}
                        </div>
                    </div>
                </div>
            `,
        };
    }

    function renderEventPostMarkup(post, metadata) {
        const title = (metadata?.title || post?.event_title || 'Untitled Event').trim();
        const description = (post?.content || metadata?.description || '').trim();
        const dateValue = metadata?.date || post?.event_date || '';
        const timeValue = metadata?.time || post?.event_time || '';
        const locationValue = metadata?.location || post?.event_location || '';

        const details = [];
        if (dateValue) {
            details.push(`
                <div class="event-detail">
                    <i class="uil uil-calendar-alt"></i>
                    <span>${formatModalEventDate(dateValue)}</span>
                </div>
            `);
        }
        if (timeValue) {
            details.push(`
                <div class="event-detail">
                    <i class="uil uil-clock"></i>
                    <span>${escapeHtml(String(timeValue))}</span>
                </div>
            `);
        }
        if (locationValue) {
            details.push(`
                <div class="event-detail">
                    <i class="uil uil-map-marker"></i>
                    <span>${escapeHtml(String(locationValue))}</span>
                </div>
            `);
        }

        const imageBlock = post?.image_url
            ? `
                <div class="photo post-image modal-type-image-wrap">
                    <img class="modal-type-image" src="${escapeHtml(post.image_url)}" alt="Event image">
                </div>
            `
            : '';

        return {
            mode: 'rich',
            html: `
                <div class="modal-post-content modal-post-event">
                    <div class="event-content">
                        <h3 class="event-title">${escapeHtml(title)}</h3>
                        ${description ? `<p class="post-text modal-post-paragraph">${formatMultilineText(description)}</p>` : ''}
                        ${details.length ? `<div class="event-details">${details.join('')}</div>` : ''}
                    </div>
                    ${imageBlock}
                </div>
            `,
        };
    }

    function renderAssignmentPostMarkup(post, metadata) {
        const title = (metadata?.title || 'Untitled Assignment').trim();
        const description = (post?.content || '').trim();
        const deadline = metadata?.deadline || '';
        const points = metadata?.points;

        const details = [];
        if (deadline) {
            details.push(`
                <div class="assignment-detail deadline">
                    <i class="uil uil-calendar-alt"></i>
                    <span>Due: ${formatModalEventDate(deadline)}</span>
                </div>
            `);
        }
        if (points !== undefined && points !== null && points !== '') {
            details.push(`
                <div class="assignment-detail points">
                    <i class="uil uil-star"></i>
                    <span>${escapeHtml(String(points))} points</span>
                </div>
            `);
        }

        return {
            mode: 'rich',
            html: `
                <div class="modal-post-content modal-post-assignment">
                    <div class="assignment-content">
                        <h3 class="assignment-title">${escapeHtml(title)}</h3>
                        ${description ? `<p class="post-text modal-post-paragraph">${formatMultilineText(description)}</p>` : ''}
                        ${details.length ? `<div class="assignment-details">${details.join('')}</div>` : ''}
                    </div>
                </div>
            `,
        };
    }

    function renderTypedPostContent(post) {
        const metadata = normalizePostMetadata(post?.metadata);
        const postType = resolveModalPostType(post);

        switch (postType) {
            case 'question':
                return renderQuestionPostMarkup(post, metadata);
            case 'resource':
                return renderResourcePostMarkup(post, metadata);
            case 'poll':
                return renderPollPostMarkup(post, metadata);
            case 'event':
                return renderEventPostMarkup(post, metadata);
            case 'assignment':
                return renderAssignmentPostMarkup(post, metadata);
            default:
                return renderDiscussionPostMarkup(post);
        }
    }

    function displayPost(index) {
        if (!currentPosts || index < 0 || index >= currentPosts.length) return;
        
        const post = currentPosts[index];
        if (!post || typeof post !== 'object') {
            return;
        }
        syncPostViewMenu(post);

        // Render type-specific content so selected posts keep their true structure (poll/event/question/etc).
        const renderedPost = renderTypedPostContent(post);
        if (renderedPost.mode === 'image-only') {
            postViewImage.src = renderedPost.imageUrl || '';
            postViewImage.style.display = 'block';
            postViewTextContent.style.display = 'none';
            postViewTextContent.classList.remove('active', 'is-rich-post', 'is-simple-text');
            postViewTextContent.innerHTML = '';
        } else if (renderedPost.mode === 'simple-text') {
            postViewImage.style.display = 'none';
            postViewTextContent.innerHTML = renderedPost.html || '<p>No content available for this post.</p>';
            postViewTextContent.style.display = 'flex';
            postViewTextContent.classList.add('active', 'is-simple-text');
            postViewTextContent.classList.remove('is-rich-post');
        } else {
            postViewImage.style.display = 'none';
            postViewTextContent.innerHTML = renderedPost.html || '<p>No content available for this post.</p>';
            postViewTextContent.style.display = 'flex';
            postViewTextContent.classList.add('active', 'is-rich-post');
            postViewTextContent.classList.remove('is-simple-text');
        }
        
        // Set user info
        const avatarUrl = post.profile_picture || (BASE_PATH + 'images/avatars/defaultProfilePic.png');
        postViewAvatar.src = avatarUrl;
        
        const fullName = (post.first_name || '') + ' ' + (post.last_name || '');
        const displayName = fullName.trim() || post.username || 'Unknown User';
        postViewUsername.textContent = displayName;
        
        postViewDate.textContent = post.created_at || '';
        
        // Set caption fallback based on content and metadata.
        const postMetadata = normalizePostMetadata(post?.metadata);
        postViewCaption.textContent = post.content
            || postMetadata.description
            || postMetadata.title
            || post.event_title
            || '';

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
                modalCommentsMeta = {
                    currentUserId: Number(data.currentUserId || 0),
                    postOwnerId: Number(data.postOwnerId || 0),
                };
                postViewCommentsList.innerHTML = renderModalComments(data.comments || [], modalCommentsMeta);
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

    function canManageComment(comment, meta = {}) {
        const currentUserId = Number(meta.currentUserId || 0);
        const postOwnerId = Number(meta.postOwnerId || 0);
        const commenterId = Number(comment?.commenter_id || comment?.commenterId || 0);
        return currentUserId > 0 && (commenterId === currentUserId || postOwnerId === currentUserId);
    }

    function getCommentDisplayName(comment) {
        const firstName = String(comment?.first_name || '').trim();
        const lastName = String(comment?.last_name || '').trim();
        const fullName = `${firstName} ${lastName}`.trim();
        return fullName || String(comment?.username || comment?.author || 'Unknown User');
    }

    function getCommentProfileLink(comment) {
        const userId = Number(comment?.commenter_id || comment?.user_id || 0);
        if (!userId) return '#';
        return `${BASE_PATH}index.php?controller=Profile&action=view&user_id=${userId}`;
    }

    function renderModalComments(comments, meta = {}) {
        if (!comments || comments.length === 0) {
            return '<div class="no-comments">No comments yet. Be the first to comment.</div>';
        }

        return comments.map(comment => {
            const author = escapeHtml(getCommentDisplayName(comment));
            const body = escapeHtml(comment.content || comment.comment || '');
            const timestamp = escapeHtml(comment.created_at || '');
            const profileLink = escapeHtml(getCommentProfileLink(comment));
            const profilePicture = escapeHtml(comment.profile_picture || `${BASE_PATH}uploads/user_dp/default_user_dp.jpg`);
            const canManage = canManageComment(comment, meta);
            const repliesHtml = (comment.replies || []).map(reply => renderModalReply(reply, meta)).join('');
            const commentId = comment.comment_id ?? comment.commentId ?? '';
            const actions = [
                `<button type="button" class="comment-action-btn comment-reply-btn" data-comment-action="reply" data-parent-id="${commentId}">Reply</button>`
            ];
            if (canManage) {
                actions.push(`<button type="button" class="comment-action-btn" data-comment-action="edit" data-comment-id="${commentId}">Edit</button>`);
                actions.push(`<button type="button" class="comment-action-btn" data-comment-action="delete" data-comment-id="${commentId}">Delete</button>`);
            }
            return `
                <div class="modal-comment" data-comment-id="${commentId}">
                    <div class="comment-header">
                        <a class="comment-author-link" href="${profileLink}">
                            <img src="${profilePicture}" alt="${author}" class="comment-avatar">
                            <span class="comment-author">${author}</span>
                        </a>
                        <span class="comment-time">${timestamp}</span>
                    </div>
                    <div class="comment-body" data-comment-content>${body}</div>
                    <div class="modal-comment-actions">${actions.join('')}</div>
                    <div class="reply-list">${repliesHtml}</div>
                </div>
            `;
        }).join('');
    }

    function renderModalReply(reply, meta = {}) {
        const author = escapeHtml(getCommentDisplayName(reply));
        const body = escapeHtml(reply.content || reply.comment || '');
        const timestamp = escapeHtml(reply.created_at || '');
        const profileLink = escapeHtml(getCommentProfileLink(reply));
        const profilePicture = escapeHtml(reply.profile_picture || `${BASE_PATH}uploads/user_dp/default_user_dp.jpg`);
        const replyId = reply.comment_id ?? reply.commentId ?? '';
        const canManage = canManageComment(reply, meta);
        return `
            <div class="modal-reply" data-comment-id="${replyId}">
                <div class="reply-header">
                    <a class="comment-author-link" href="${profileLink}">
                        <img src="${profilePicture}" alt="${author}" class="comment-avatar">
                        <span class="comment-author">${author}</span>
                    </a>
                    <span class="comment-time">${timestamp}</span>
                </div>
                <div class="reply-body" data-comment-content>${body}</div>
                ${canManage ? `<div class="modal-comment-actions"><button type="button" class="comment-action-btn" data-comment-action="edit" data-comment-id="${replyId}">Edit</button><button type="button" class="comment-action-btn" data-comment-action="delete" data-comment-id="${replyId}">Delete</button></div>` : ''}
            </div>
        `;
    }

    async function editModalComment(commentId) {
        const root = postViewCommentsList?.querySelector(`[data-comment-id="${commentId}"]`);
        const contentElement = root ? root.querySelector('[data-comment-content]') : null;
        const currentValue = (contentElement?.textContent || '').trim();
        const newValue = window.prompt('Edit comment', currentValue);

        if (newValue === null) return;
        const trimmed = newValue.trim();
        if (!trimmed) {
            window.showToast?.('Comment cannot be empty', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('sub_action', 'edit');
        formData.append('comment_id', String(commentId));
        formData.append('content', trimmed);

        const response = await fetch(BASE_PATH + 'index.php?controller=Comment&action=handleAjax', {
            method: 'POST',
            body: formData,
        });
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Unable to update comment.');
        }

        if (contentElement) {
            contentElement.textContent = trimmed;
        }
    }

    async function deleteModalComment(commentId) {
        const formData = new FormData();
        formData.append('sub_action', 'delete');
        formData.append('comment_id', String(commentId));

        const response = await fetch(BASE_PATH + 'index.php?controller=Comment&action=handleAjax', {
            method: 'POST',
            body: formData,
        });
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Unable to delete comment.');
        }

        if (data.comment_count !== undefined) {
            updateCommentBadges(Number(data.comment_count || 0));
        }
        await loadModalComments(postViewModal?.dataset.postId);
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
        form.className = 'reply-form hf-form hf-inline';
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
            const directReplyButton = event.target.closest('.comment-reply-btn');
            if (directReplyButton) {
                event.preventDefault();
                event.stopPropagation();
                const commentBlock = directReplyButton.closest('.modal-comment');
                if (!commentBlock) return;
                const parentId = directReplyButton.dataset.parentId || commentBlock.dataset.commentId;
                if (!parentId) return;
                const replyAnchor = commentBlock.querySelector('.reply-list') || commentBlock;
                attachReplyForm(parentId, replyAnchor);
                return;
            }

            const actionButton = event.target.closest('[data-comment-action]');
            if (actionButton) {
                event.preventDefault();
                const action = actionButton.dataset.commentAction;

                if (action === 'reply') {
                    const parentId = actionButton.dataset.parentId;
                    const commentBlock = actionButton.closest('.modal-comment');
                    if (!commentBlock || !parentId) return;
                    const replyAnchor = commentBlock.querySelector('.reply-list') || commentBlock;
                    attachReplyForm(parentId, replyAnchor);
                    return;
                }

                const commentId = Number(actionButton.dataset.commentId || 0);
                if (!commentId) return;

                if (action === 'edit') {
                    editModalComment(commentId).catch((error) => {
                        console.error('Edit modal comment failed:', error);
                        window.showToast?.(error?.message || 'Unable to update comment', 'error');
                    });
                    return;
                }

                if (action === 'delete') {
                    const confirmed = window.confirm('Delete this comment?');
                    if (!confirmed) return;

                    deleteModalComment(commentId).catch((error) => {
                        console.error('Delete modal comment failed:', error);
                        window.showToast?.(error?.message || 'Unable to delete comment', 'error');
                    });
                    return;
                }
            }

            const replyTrigger = event.target.closest('.comment-reply-btn');
            if (!replyTrigger) return;
            event.preventDefault();
            event.stopPropagation();
            const parentId = replyTrigger.dataset.parentId;
            const commentBlock = replyTrigger.closest('.modal-comment');
            if (!commentBlock || !parentId) return;
            const replyAnchor = commentBlock.querySelector('.reply-list') || commentBlock;
            attachReplyForm(parentId, replyAnchor);
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

    if (postViewMenuTrigger && postViewMenuDropdown) {
        postViewMenuTrigger.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            if (isPostViewMenuOpen()) closePostViewMenu();
            else openPostViewMenu();
        });
    }

    if (postViewMenuDropdown) {
        postViewMenuDropdown.addEventListener('click', (event) => {
            event.stopPropagation();
            const menuButton = event.target.closest('.post-view-menu-item');
            if (!menuButton) return;
            if (menuButton.id !== 'postViewReportAction') {
                event.preventDefault();
            }

            const post = currentPosts[currentPostIndex];
            const postId = Number(post?.post_id || post?.postId || 0);
            if (!postId) {
                closePostViewMenu();
                return;
            }

            if (menuButton.id === 'postViewEditAction') {
                const currentText = String(post?.content || '').trim();
                const edited = window.prompt('Edit post', currentText);
                if (edited === null) {
                    closePostViewMenu();
                    return;
                }
                const cleaned = edited.trim();
                if (!cleaned) {
                    window.showToast?.('Post content cannot be empty', 'error');
                    closePostViewMenu();
                    return;
                }

                updateCurrentPostContent(cleaned)
                    .then(() => {
                        displayPost(currentPostIndex);
                        window.showToast?.('Post updated', 'success');
                    })
                    .catch((error) => {
                        console.error('Modal post update failed:', error);
                        window.showToast?.(error?.message || 'Unable to update post', 'error');
                    })
                    .finally(closePostViewMenu);
                return;
            }

            if (menuButton.id === 'postViewDeleteAction') {
                const confirmed = window.confirm('Delete this post? This cannot be undone.');
                if (!confirmed) {
                    closePostViewMenu();
                    return;
                }

                deleteCurrentPost()
                    .then(() => {
                        window.showToast?.('Post deleted', 'success');
                    })
                    .catch((error) => {
                        console.error('Modal post delete failed:', error);
                        window.showToast?.(error?.message || 'Unable to delete post', 'error');
                    })
                    .finally(closePostViewMenu);
                return;
            }

            closePostViewMenu();
        });
    }

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (postViewModal && postViewModal.classList.contains('active')) {
            if (e.key === 'Escape') {
                closePostViewMenu();
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
        if (postViewMenu && !postViewMenu.contains(e.target)) {
            closePostViewMenu();
        }

        const postGridItem = e.target.closest('.post-grid-item');
        if (postGridItem && postGridItem.hasAttribute('data-post-index')) {
            e.preventDefault();
            e.stopPropagation();
            const index = parseInt(postGridItem.getAttribute('data-post-index') || '', 10);
            if (!Number.isInteger(index)) return;
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

        if (hash.startsWith('personal-post-') || hash.startsWith('group-post-') || hash.startsWith('saved-post-')) {
            const parts = hash.split('-');
            const postId = parseInt(parts[parts.length - 1], 10);
            const postType = hash.startsWith('group-post-')
                ? 'group'
                : (hash.startsWith('saved-post-') ? 'saved' : 'personal');

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
