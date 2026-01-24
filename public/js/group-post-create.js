// Group Post Creation Module
document.addEventListener('DOMContentLoaded', function() {
    // ===== CREATE POST MODAL =====
    const createPostModal = document.getElementById('createPostModal');
    const quickPostTrigger = document.getElementById('quickPostTrigger');
    const photoQuickBtn = document.getElementById('photoQuickBtn');
    const pollQuickBtn = document.getElementById('pollQuickBtn');
    const questionQuickBtn = document.getElementById('questionQuickBtn');
    const resourceQuickBtn = document.getElementById('resourceQuickBtn');
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

    // Open modal
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

    function closeCreatePostModalFn() {
        if (createPostModal) {
            createPostModal.style.display = 'none';
            document.body.style.overflow = '';
            resetCreatePostForm();
        }
    }

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

    // Event listeners for quick action buttons
    if (quickPostTrigger) {
        quickPostTrigger.addEventListener('click', () => openCreatePostModal('discussion'));
    }

    if (photoQuickBtn) {
        photoQuickBtn.addEventListener('click', () => {
            openCreatePostModal('discussion');
            setTimeout(() => {
                if (uploadImageBtn) uploadImageBtn.click();
            }, 100);
        });
    }

    if (pollQuickBtn) {
        pollQuickBtn.addEventListener('click', () => openCreatePostModal('poll'));
    }

    if (questionQuickBtn) {
        questionQuickBtn.addEventListener('click', () => openCreatePostModal('question'));
    }

    if (resourceQuickBtn) {
        resourceQuickBtn.addEventListener('click', () => openCreatePostModal('resource'));
    }

    if (closeCreatePostModal) {
        closeCreatePostModal.addEventListener('click', closeCreatePostModalFn);
    }

    if (cancelCreatePostBtn) {
        cancelCreatePostBtn.addEventListener('click', closeCreatePostModalFn);
    }

    // Post type button switching
    postTypeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            postTypeBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const type = this.getAttribute('data-type');
            if (selectedPostType) selectedPostType.value = type;
            showConditionalFields(type);
        });
    });

    // Image upload handling
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

    // File upload handling (for resources)
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
                // Add sub_action parameter for the GroupController
                formData.append('sub_action', 'createPost');

                const response = await fetch(BASE_PATH + 'index.php?controller=Group&action=handleAjax', {
                    method: 'POST',
                    body: formData
                });

                const text = await response.text();
                console.log('Create post response:', text);
                
                const result = JSON.parse(text);

                if (result.success) {
                    if (window.showToast) {
                        window.showToast('Post created successfully!', 'success');
                    } else if (typeof showToast === 'function') {
                        showToast('Post created successfully!', 'success');
                    } else {
                        alert('Post created successfully!');
                    }
                    closeCreatePostModalFn();
                    
                    // Reload page to show new post
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    if (window.showToast) {
                        window.showToast(result.message || 'Failed to create post', 'error');
                    } else if (typeof showToast === 'function') {
                        showToast(result.message || 'Failed to create post', 'error');
                    } else {
                        alert(result.message || 'Failed to create post');
                    }
                }
            } catch (error) {
                console.error('Create post error:', error);
                if (window.showToast) {
                    window.showToast('Failed to create post', 'error');
                } else if (typeof showToast === 'function') {
                    showToast('Failed to create post', 'error');
                } else {
                    alert('Failed to create post');
                }
            } finally {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }
        });
    }
});
