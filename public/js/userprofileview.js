document.addEventListener('DOMContentLoaded', () => {
    const tabLinks = document.querySelectorAll('.profile-tabs a');
    const tabPanels = document.querySelectorAll('.group-content .tab-content');

    const setActiveTab = (target, options = {}) => {
        if (!target) {
            return;
        }

        const normalized = target.replace(/^tab-/, '');
        let activeLink = null;
        tabLinks.forEach((link) => {
            const linkTarget = link.getAttribute('data-tab');
            const isActive = linkTarget === normalized;
            const parent = link.parentElement;
            if (parent) {
                parent.classList.toggle('active', isActive);
            }
            link.setAttribute('aria-selected', isActive ? 'true' : 'false');
            link.setAttribute('tabindex', isActive ? '0' : '-1');
            if (isActive) {
                activeLink = link;
            }
        });

        tabPanels.forEach((panel) => {
            const isActive = panel.id === `tab-${normalized}`;
            panel.classList.toggle('active', isActive);
        });

        if (options.scroll) {
            const activePanel = document.getElementById(`tab-${normalized}`);
            if (activePanel) {
                const offset = window.innerWidth <= 768 ? 64 : 96;
                const panelTop = activePanel.getBoundingClientRect().top + window.pageYOffset - offset;
                window.scrollTo({
                    top: panelTop < 0 ? 0 : panelTop,
                    behavior: 'smooth'
                });
            }
            if (activeLink && typeof activeLink.focus === 'function') {
                try {
                    activeLink.focus({ preventScroll: true });
                } catch (error) {
                    activeLink.focus();
                }
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
    if (defaultActiveLink) {
        setActiveTab(defaultActiveLink.getAttribute('data-tab'));
    }

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

    const normalizedBasePath = BASE_PATH.endsWith('/') ? BASE_PATH : `${BASE_PATH}/`;
    const buildPublicUrl = (path) => {
        if (!path) {
            return null;
        }
        if (/^https?:\/\//i.test(path)) {
            return path;
        }
        let normalized = path.replace(/^\/+/g, '');
        if (!normalized.startsWith('public/')) {
            normalized = `public/${normalized}`;
        }
        return `${normalizedBasePath}${normalized}`;
    };
    const addCacheBuster = (url) => {
        if (!url) {
            return url;
        }
        const separator = url.includes('?') ? '&' : '?';
        return `${url}${separator}_=${Date.now()}`;
    };

    const openModal = () => {
        if (!modal) {
            return;
        }
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
        if (!modal) {
            return;
        }
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    };

    if (editProfileBtn && modal) {
        editProfileBtn.addEventListener('click', openModal);
    }
    if (modal) {
        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', closeModal);
        }
        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeModal);
        }
        modal.addEventListener('click', event => {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    const showMessage = (text, type) => {
        if (!messageBox) {
            return;
        }
        messageBox.textContent = text;
        messageBox.className = `form-message ${type}`;
        messageBox.style.display = 'block';
    };

    const updatePreview = (input, preview, displayTarget) => {
        if (!input || !preview) {
            return;
        }
        input.addEventListener('change', event => {
            const file = event.target.files && event.target.files[0];
            if (!file) {
                return;
            }
            const reader = new FileReader();
            reader.onload = e => {
                if (preview) {
                    preview.src = e.target.result;
                }
                if (displayTarget) {
                    displayTarget.src = e.target.result;
                }
            };
            reader.readAsDataURL(file);
        });
    };

    updatePreview(profilePictureInput, profilePreview, avatarDisplay);
    updatePreview(coverPhotoInput, coverPreview, coverDisplay);

    if (form) {
        form.addEventListener('submit', async event => {
            event.preventDefault();

            const formData = new FormData(form);

            showMessage('Saving profile...', 'info');

            try {
                const response = await fetch(`${normalizedBasePath}index.php?controller=Profile&action=update`, {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`Request failed with status ${response.status}`);
                }

                const result = await response.json();

                if (result.success) {
                    showMessage(result.message || 'Profile updated.', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);
                } else {
                    showMessage(result.message || 'Failed to update profile.', 'error');
                }
            } catch (error) {
                showMessage('An unexpected error occurred. Please try again.', 'error');
                console.error('Profile update failed:', error);
            }
        });
    }

    const setLoadingState = (button, isLoading, loadingContent) => {
        if (!button) {
            return;
        }
        if (!button.dataset.originalContent) {
            button.dataset.originalContent = button.innerHTML;
        }
        if (isLoading) {
            button.disabled = true;
            button.classList.add('uploading');
            button.setAttribute('aria-busy', 'true');
            if (loadingContent) {
                button.innerHTML = loadingContent;
            }
        } else {
            button.disabled = false;
            button.classList.remove('uploading');
            button.removeAttribute('aria-busy');
            if (button.dataset.originalContent) {
                button.innerHTML = button.dataset.originalContent;
            }
        }
    };

    const flashSuccess = (button, successContent) => {
        if (!button || !successContent) {
            return;
        }
        const original = button.dataset.originalContent || button.innerHTML;
        button.innerHTML = successContent;
        setTimeout(() => {
            button.innerHTML = original;
        }, 1500);
    };

    const handleDirectUpload = ({
        input,
        endpoint,
        trigger,
        loadingContent,
        successContent,
        responseKey,
        previewTargets
    }) => {
        if (!input || !trigger) {
            return;
        }

        input.addEventListener('change', async event => {
            const file = event.target.files && event.target.files[0];
            if (!file) {
                return;
            }

            const formData = new FormData();
            formData.append(input.name || responseKey, file);

            let wasSuccessful = false;

            setLoadingState(trigger, true, loadingContent);

            try {
                const response = await fetch(`${normalizedBasePath}index.php?controller=Profile&action=${endpoint}`, {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`Request failed with status ${response.status}`);
                }

                const result = await response.json();

                if (result.success && result[responseKey]) {
                    const mediaUrl = addCacheBuster(buildPublicUrl(result[responseKey]));
                    previewTargets.forEach(target => {
                        if (target && mediaUrl) {
                            target.src = mediaUrl;
                        }
                    });
                    wasSuccessful = true;
                } else {
                    alert(result.message || 'Unable to update image.');
                }
            } catch (error) {
                console.error(`Failed to update ${responseKey}:`, error);
                alert('An unexpected error occurred while uploading the image.');
            } finally {
                setLoadingState(trigger, false);
                if (wasSuccessful) {
                    flashSuccess(trigger, successContent);
                }
                if (input) {
                    input.value = '';
                }
            }
        });
    };

    if (triggerEditAvatar && directAvatarInput) {
        triggerEditAvatar.addEventListener('click', () => directAvatarInput.click());
        handleDirectUpload({
            input: directAvatarInput,
            endpoint: 'updateProfilePicture',
            trigger: triggerEditAvatar,
            loadingContent: '<i class="uil uil-cloud-upload"></i>',
            successContent: '<i class="uil uil-check"></i>',
            responseKey: 'profile_picture',
            previewTargets: [avatarDisplay, profilePreview, ...navAvatarImages, ...sidebarAvatarImages]
        });
    }

    if (triggerEditCover && directCoverInput) {
        triggerEditCover.addEventListener('click', () => directCoverInput.click());
        handleDirectUpload({
            input: directCoverInput,
            endpoint: 'updateCoverPhoto',
            trigger: triggerEditCover,
            loadingContent: '<i class="uil uil-cloud-upload"></i> Uploading...',
            successContent: '<i class="uil uil-check"></i> Saved',
            responseKey: 'cover_photo',
            previewTargets: [coverDisplay, coverPreview]
        });
    }

    const friendListModal = document.getElementById('friendListModal');
    let lastFriendTrigger = null;

    const friendTriggers = document.querySelectorAll('[data-friend-count-trigger]');

    const openFriendListModal = (trigger) => {
        if (!friendListModal) {
            return;
        }
        lastFriendTrigger = trigger || null;
        friendListModal.classList.add('active');
        friendListModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        const closeButton = friendListModal.querySelector('[data-close-friends-modal]');
        if (closeButton) {
            setTimeout(() => closeButton.focus(), 10);
        }
    };

    const closeFriendListModal = () => {
        if (!friendListModal) {
            return;
        }
        friendListModal.classList.remove('active');
        friendListModal.setAttribute('aria-hidden', 'true');

        const editModalIsOpen = document.querySelector('.profile-edit-modal.active');
        if (!editModalIsOpen) {
            document.body.style.overflow = '';
        }

        if (lastFriendTrigger && typeof lastFriendTrigger.focus === 'function') {
            lastFriendTrigger.focus();
        }
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

        friendListModal.addEventListener('click', (event) => {
            if (event.target === friendListModal) {
                closeFriendListModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && friendListModal.classList.contains('active')) {
                closeFriendListModal();
            }
        });
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
});
